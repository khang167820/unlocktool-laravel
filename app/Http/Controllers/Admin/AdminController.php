<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Account;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Admin Dashboard - Statistics overview
     */
    public function dashboard()
    {
        // Revenue stats - 1 query
        $revenue = DB::table('orders')
            ->whereIn('status', ['paid', 'completed'])
            ->whereNotNull('paid_at')
            ->select(
                DB::raw("SUM(CASE WHEN DATE(paid_at) = CURDATE() THEN amount ELSE 0 END) as today"),
                DB::raw("SUM(CASE WHEN paid_at >= '" . now()->startOfWeek()->format('Y-m-d H:i:s') . "' THEN amount ELSE 0 END) as week"),
                DB::raw("SUM(CASE WHEN paid_at >= '" . now()->startOfMonth()->format('Y-m-d H:i:s') . "' THEN amount ELSE 0 END) as month")
            )
            ->first();
        
        $todayRevenue = $revenue->today ?? 0;
        $weekRevenue = $revenue->week ?? 0;
        $monthRevenue = $revenue->month ?? 0;
        
        // Order stats - 1 query
        $orderStats = DB::table('orders')
            ->select(
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today")
            )
            ->first();
        
        $totalOrders = $orderStats->total ?? 0;
        $pendingOrders = $orderStats->pending ?? 0;
        $paidOrders = $orderStats->paid ?? 0;
        $completedOrders = $orderStats->completed ?? 0;
        $todayOrders = $orderStats->today ?? 0;
        
        // Account stats
        $accountStats = DB::table('accounts')
            ->select(
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available"),
                DB::raw("SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as renting")
            )
            ->first();
        
        $totalAccounts = $accountStats->total ?? 0;
        $availableAccounts = $accountStats->available ?? 0;
        $rentingAccounts = $accountStats->renting ?? 0;
        
        // Blog stats
        $blogStats = ['total' => 0, 'published' => 0];
        try {
            $blogStats = [
                'total' => DB::table('blog_posts')->count(),
                'published' => DB::table('blog_posts')->where('status', 'published')->count(),
            ];
        } catch (\Exception $e) {}
        
        // Recent orders
        $recentOrders = Order::orderBy('created_at', 'desc')->limit(10)->get();
        
        return view('admin.dashboard', compact(
            'todayRevenue', 'weekRevenue', 'monthRevenue',
            'totalOrders', 'pendingOrders', 'paidOrders', 'completedOrders', 'todayOrders',
            'totalAccounts', 'availableAccounts', 'rentingAccounts',
            'blogStats', 'recentOrders'
        ));
    }
    
    // ==================== ORDERS ====================
    
    public function orders(Request $request)
    {
        $query = Order::with('account')->orderBy('created_at', 'desc');
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $query->where('tracking_code', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('account')) {
            $query->whereHas('account', function($q) use ($request) {
                $q->where('username', 'like', '%' . $request->account . '%');
            });
        }
        
        $orders = $query->paginate(100)->withQueryString();
        
        $statusCounts = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');
        
        return view('admin.orders.index', compact('orders', 'statusCounts'));
    }
    
    public function updateOrderStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->status;
        $order->status = $newStatus;
        
        if ($newStatus === 'paid') {
            $order->paid_at = now();
        } elseif ($newStatus === 'completed') {
            $order->completed_at = now();
        }
        
        $order->save();
        
        // Auto-allocate account when changing to paid
        $shouldAllocate = false;
        if ($oldStatus === 'pending' && $newStatus === 'paid') {
            $shouldAllocate = true;
        } elseif (in_array($newStatus, ['paid', 'completed']) && empty($order->account_id)) {
            $shouldAllocate = true;
        }
        
        if ($shouldAllocate) {
            $order->refresh();
            
            $allocationResult = \App\Services\AccountAllocationService::allocateAccount($order);
            
            if ($allocationResult['success']) {
                return back()->with('success', 'Đã thanh toán & cấp tài khoản thành công!');
            } else {
                \Log::warning("Admin allocation failed for order: {$order->tracking_code}, error: {$allocationResult['error']}");
                return back()->with('warning', 'Đã thanh toán nhưng không thể cấp tài khoản: ' . $allocationResult['error']);
            }
        }
        
        return back()->with('success', 'Cập nhật trạng thái đơn hàng thành công!');
    }
    
    /**
     * Reissue password for an order's account.
     */
    public function reissueOrderPassword(Request $request, $id)
    {
        $order = Order::with('account')->findOrFail($id);
        
        if (!$order->account) {
            return back()->with('error', 'Đơn hàng chưa được cấp tài khoản!');
        }
        
        $request->validate([
            'new_password' => 'required|string|min:1',
            'extend_hours' => 'nullable|integer|min:0',
        ]);
        
        DB::table('accounts')->where('id', $order->account->id)->update([
            'password' => $request->new_password,
            'password_changed' => 0,
            'is_available' => 0,
        ]);
        
        $extendHours = (int) $request->input('extend_hours', 0);
        if ($extendHours > 0) {
            $currentExpiry = $order->expires_at;
            $baseTime = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry : now();
            $newExpiry = $baseTime->copy()->addHours($extendHours);
            $order->expires_at = $newExpiry;
            $order->save();
        }
        
        Log::info("Admin reissued password for order #{$order->tracking_code}, account: {$order->account->username}, extend: {$extendHours}h");
        
        $msg = "Đã cấp lại MK cho {$order->tracking_code} (TK: {$order->account->username})";
        if ($extendHours > 0) $msg .= " + gia hạn {$extendHours}h";
        
        return back()->with('success', $msg);
    }
    
    // ==================== ACCOUNTS ====================
    
    public function accounts(Request $request)
    {
        // Advanced query with sorting logic
        $latestOrders = DB::table('orders')
            ->select('account_id', DB::raw('MAX(expires_at) as latest_expires_at'))
            ->whereIn('status', ['paid', 'completed'])
            ->groupBy('account_id');

        // Sắp xếp theo thứ tự:
        // 1. Đang thuê - Hết hạn - CHƯA ghi chú (cần xử lý gấp)
        // 2. Đang thuê - Còn thời gian (không ghi chú)
        // 3. Đang thuê - Còn thời gian + có ghi chú
        // 4. Đang thuê - Hết hạn - ĐÃ ghi chú (đã biết rồi)
        // 5. Đang thuê - Có ghi chú (không có order)
        // 6. Đang thuê - Khác
        // 7. Chờ thuê
        $accounts = DB::table('accounts')
            ->leftJoinSub($latestOrders, 'latest_orders', function ($join) {
                $join->on('accounts.id', '=', 'latest_orders.account_id');
            })
            ->select('accounts.*', 'latest_orders.latest_expires_at as sorting_expires_at')
            ->orderByRaw("
                CASE 
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at IS NOT NULL AND latest_orders.latest_expires_at < NOW() AND (accounts.note IS NULL OR accounts.note = '') THEN 1
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at IS NOT NULL AND latest_orders.latest_expires_at >= NOW() AND (accounts.note IS NULL OR accounts.note = '') THEN 2
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at IS NOT NULL AND latest_orders.latest_expires_at >= NOW() AND accounts.note IS NOT NULL AND accounts.note != '' THEN 3
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at IS NOT NULL AND latest_orders.latest_expires_at < NOW() AND accounts.note IS NOT NULL AND accounts.note != '' THEN 4
                    WHEN accounts.is_available = 0 AND accounts.note IS NOT NULL AND accounts.note != '' THEN 5
                    WHEN accounts.is_available = 0 THEN 6
                    ELSE 7
                END ASC
            ")
            ->orderByRaw("
                CASE 
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at IS NOT NULL AND latest_orders.latest_expires_at >= NOW() 
                        THEN latest_orders.latest_expires_at
                    ELSE NULL 
                END ASC
            ")
            ->orderByRaw("
                CASE 
                    WHEN accounts.is_available = 1 THEN COALESCE(latest_orders.latest_expires_at, accounts.created_at)
                    ELSE NULL 
                END ASC
            ")
            ->orderBy('accounts.id', 'asc')
            ->paginate(50)
            ->withQueryString();
        
        // Get rental info for rented accounts
        $rentedAccountIds = collect($accounts->items())
            ->where('is_available', 0)
            ->pluck('id')
            ->toArray();
        
        $rentalInfo = [];
        if (!empty($rentedAccountIds)) {
            // Get only the LATEST order per account (highest ID = most recent)
            $latestOrderIds = DB::table('orders')
                ->select(DB::raw('MAX(id) as id'))
                ->whereIn('account_id', $rentedAccountIds)
                ->whereIn('status', ['paid', 'completed'])
                ->whereNotNull('expires_at')
                ->groupBy('account_id')
                ->pluck('id');
            
            $rentalInfo = DB::table('orders')
                ->whereIn('id', $latestOrderIds)
                ->select('account_id', 'tracking_code', 'expires_at', 'ip_address')
                ->get()
                ->keyBy('account_id');
        }
        
        foreach ($accounts as $account) {
            if (isset($rentalInfo[$account->id])) {
                $rental = $rentalInfo[$account->id];
                $account->rental_order_code = $rental->tracking_code;
                $account->rental_expires_at = $rental->expires_at;
                $account->renter_ip = $rental->ip_address;
            }
        }
        
        // Stats
        $statsRaw = DB::table('accounts')
            ->select(
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available"),
                DB::raw("SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as renting")
            )
            ->first();
        
        $stats = [
            'total' => $statsRaw->total ?? 0,
            'available' => $statsRaw->available ?? 0,
            'renting' => $statsRaw->renting ?? 0,
        ];
        
        return view('admin.accounts.index', compact('accounts', 'stats'));
    }
    
    public function addAccount(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'note' => 'nullable|string',
            'expires_at' => 'nullable|date',
        ]);
        
        DB::table('accounts')->insert([
            'username' => $data['username'],
            'password' => $data['password'],
            'type' => 'Unlocktool',
            'is_available' => 1,
            'note' => $data['note'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
        
        return back()->with('success', 'Account added!');
    }
    
    public function toggleAccount($id)
    {
        $account = DB::table('accounts')->where('id', $id)->first();
        if (!$account) return redirect()->route('admin.accounts')->with('error', 'Tài khoản không tồn tại!');
        
        $status = request()->input('status');
        if ($status === 'available') {
            $newAvailable = true;
        } elseif ($status === 'renting') {
            $newAvailable = false;
        } else {
            $newAvailable = !$account->is_available;
        }
        
        // Check note: use form value if sent from edit page, otherwise use DB value
        $currentNote = request()->has('note') ? request()->input('note') : $account->note;
        
        // Block switching to available if account still has a note
        if ($newAvailable && !empty($currentNote)) {
            return redirect()->route('admin.accounts')->with('error', 'Phải xóa ghi chú trước khi chuyển sang Chờ thuê!');
        }
        
        $updateData = ['is_available' => $newAvailable ? 1 : 0];
        
        // If toggling to available, also clear note and note_date
        if ($newAvailable) {
            $updateData['note'] = null;
            $updateData['note_date'] = null;
            $updateData['password_changed'] = 0;
        }
        
        // Also save password if provided (from edit page)
        $password = request()->input('password');
        if (!empty($password)) {
            $updateData['password'] = $password;
        }
        
        DB::table('accounts')->where('id', $id)->update($updateData);
        
        // If toggling to available, expire any active orders for this account
        if ($newAvailable) {
            DB::table('orders')
                ->where('account_id', $id)
                ->whereIn('status', ['paid', 'completed'])
                ->where('expires_at', '>', now())
                ->update(['expires_at' => now()]);
        }
        
        return redirect()->route('admin.accounts')->with('success', 'Đã cập nhật trạng thái tài khoản!');
    }
    
    public function updateAccount(Request $request, $id)
    {
        $data = [];
        if ($request->has('username')) $data['username'] = $request->username;
        if ($request->has('password')) $data['password'] = $request->password;
        if ($request->has('note')) $data['note'] = $request->note;
        if ($request->has('note_date')) $data['note_date'] = $request->note_date;
        if ($request->has('expires_at')) $data['expires_at'] = $request->expires_at;
        
        // Nếu thêm ghi chú → tự động chuyển Đang thuê
        if ($request->has('note') && !empty($request->note)) {
            $data['is_available'] = 0;
        }
        
        if (!empty($data)) {
            DB::table('accounts')->where('id', $id)->update($data);
        }
        
        return redirect()->route('admin.accounts')->with('success', 'Cập nhật tài khoản thành công!');
    }
    
    public function deleteAccount($id)
    {
        DB::table('accounts')->where('id', $id)->delete();
        return redirect()->route('admin.accounts')->with('success', 'Account deleted!');
    }
    
    public function editAccount($id)
    {
        $account = DB::table('accounts')->where('id', $id)->first();
        if (!$account) return redirect()->route('admin.accounts')->with('error', 'Tài khoản không tồn tại!');
        return view('admin.accounts.edit', compact('account'));
    }
    
    /**
     * Change Account Password
     */
    public function changeAccountPassword(Request $request, $id)
    {
        $account = DB::table('accounts')->where('id', $id)->first();
        if (!$account) return redirect()->route('admin.accounts')->with('error', 'Tài khoản không tồn tại!');
        
        DB::table('accounts')->where('id', $id)->update([
            'password' => $request->password,
            'password_changed' => 1,
            'updated_at' => now(),
        ]);
        
        return redirect()->route('admin.accounts')->with('success', 'Đã đổi mật khẩu thành công!');
    }
    
    /**
     * Reset Account Time
     * - Đang thuê: set latest order expires_at to NOW (expired)
     * - Chờ thuê: reset idle timer (set latest order expires_at to NOW)
     */
    public function resetAccountTG($id)
    {
        $account = DB::table('accounts')->where('id', $id)->first();
        if (!$account) return redirect()->route('admin.accounts')->with('error', 'Tài khoản không tồn tại!');
        
        $latestOrder = DB::table('orders')
            ->where('account_id', $id)
            ->orderBy('expires_at', 'desc')
            ->first();
        
        if ($latestOrder) {
            DB::table('orders')->where('id', $latestOrder->id)->update([
                'expires_at' => now(),
            ]);
            $msg = 'Đã reset thời gian thành công!';
        } else {
            DB::table('accounts')->where('id', $id)->update([
                'created_at' => now(),
            ]);
            $msg = 'Đã reset thời gian (từ ngày tạo)!';
        }
        
        return redirect()->route('admin.accounts')->with('success', $msg);
    }
    
    public function batchToggleAccounts(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->route('admin.accounts')->with('error', 'No accounts selected!');
        }
        
        // Filter out accounts that still have notes
        $accountsWithNotes = DB::table('accounts')
            ->whereIn('id', $ids)
            ->whereNotNull('note')
            ->where('note', '!=', '')
            ->pluck('username')
            ->toArray();
        
        $idsWithoutNotes = DB::table('accounts')
            ->whereIn('id', $ids)
            ->where(function($q) {
                $q->whereNull('note')->orWhere('note', '');
            })
            ->pluck('id')
            ->toArray();
        
        if (empty($idsWithoutNotes) && !empty($accountsWithNotes)) {
            return redirect()->route('admin.accounts')->with('error', 'Tất cả tài khoản đã chọn đều có ghi chú. Phải xóa ghi chú trước khi chuyển sang Chờ thuê!');
        }
        
        $affected = DB::table('accounts')
            ->whereIn('id', $idsWithoutNotes)
            ->update([
                'is_available' => 1,
                'note' => null,
                'note_date' => null,
                'password_changed' => 0,
            ]);
        
        // Expire any active orders for these accounts
        DB::table('orders')
            ->whereIn('account_id', $idsWithoutNotes)
            ->whereIn('status', ['paid', 'completed'])
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);
        
        $message = "Đã chuyển {$affected} tài khoản sang Chờ thuê!";
        if (!empty($accountsWithNotes)) {
            $skipped = implode(', ', $accountsWithNotes);
            return redirect()->route('admin.accounts')->with('warning', "{$message} Bỏ qua (có ghi chú): {$skipped}");
        }
        
        return redirect()->route('admin.accounts')->with('success', $message);
    }
    
    // ==================== PRICES ====================
    
    public function prices(Request $request)
    {
        $prices = DB::table('prices')
            ->orderBy('hours', 'asc')
            ->get();
        
        return view('admin.prices.index', compact('prices'));
    }
    
    public function savePrice(Request $request, $id = null)
    {
        $data = $request->validate([
            'hours' => 'required|integer|min:1',
            'price' => 'required|integer|min:1000',
            'original_price' => 'nullable|integer',
            'discount_percent' => 'nullable|integer',
            'promo_badge' => 'nullable|string',
            'promo_end' => 'nullable|date',
        ]);
        
        $priceData = [
            'hours' => $data['hours'],
            'price' => $data['price'],
            'type' => 'Unlocktool',
            'original_price' => $data['original_price'] ?? null,
            'discount_percent' => $data['discount_percent'] ?? null,
            'promo_badge' => $data['promo_badge'] ?? null,
            'promo_end' => $data['promo_end'] ?? null,
        ];
        
        if ($id) {
            DB::table('prices')->where('id', $id)->update($priceData);
            $message = 'Price updated!';
        } else {
            DB::table('prices')->insert($priceData);
            $message = 'Price added!';
        }
        
        return back()->with('success', $message);
    }
    
    public function deletePrice($id)
    {
        DB::table('prices')->where('id', $id)->delete();
        return back()->with('success', 'Price deleted!');
    }
    
    // ==================== BLOG ====================
    
    public function blog(Request $request)
    {
        try {
            $query = DB::table('blog_posts')->orderBy('created_at', 'desc');
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            $posts = $query->paginate(20)->withQueryString();
            
            $stats = [
                'total' => DB::table('blog_posts')->count(),
                'published' => DB::table('blog_posts')->where('status', 'published')->count(),
                'draft' => DB::table('blog_posts')->where('status', 'draft')->count(),
            ];
        } catch (\Exception $e) {
            $posts = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            $stats = ['total' => 0, 'published' => 0, 'draft' => 0];
        }
        
        return view('admin.blog.index', compact('posts', 'stats'));
    }
    
    public function blogEdit($id = null)
    {
        $post = null;
        try {
            if ($id) {
                $post = DB::table('blog_posts')->where('id', $id)->first();
            }
        } catch (\Exception $e) {}
        
        return view('admin.blog.edit', compact('post'));
    }
    
    public function blogSave(Request $request, $id = null)
    {
        $data = $request->validate([
            'title' => 'required|string|max:500',
            'slug' => 'required|string|max:500',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'image' => 'nullable|string',
            'og_image' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'schema_type' => 'nullable|string|max:50',
            'schema_json' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'status' => 'required|in:draft,published',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'focus_keyword' => 'nullable|string|max:200',
            'robots_meta' => 'nullable|string|max:100',
            'is_cornerstone' => 'boolean',
        ]);
        
        $data['is_cornerstone'] = $request->has('is_cornerstone') ? 1 : 0;
        $data['robots_meta'] = $request->input('robots_meta', 'index, follow');
        $data['author'] = 'UnlockTool.us Team';
        $data['updated_at'] = now();
        
        if ($id) {
            DB::table('blog_posts')->where('id', $id)->update($data);
            $message = 'Post updated!';
        } else {
            $data['created_at'] = now();
            $data['views'] = 0;
            DB::table('blog_posts')->insertGetId($data);
            $message = 'Post created!';
        }
        
        return redirect()->route('admin.blog')->with('success', $message);
    }
    
    public function blogDelete($id)
    {
        DB::table('blog_posts')->where('id', $id)->delete();
        return back()->with('success', 'Post deleted!');
    }
    
    public function blogToggle($id)
    {
        $post = DB::table('blog_posts')->where('id', $id)->first();
        $newStatus = $post->status === 'published' ? 'draft' : 'published';
        DB::table('blog_posts')->where('id', $id)->update(['status' => $newStatus]);
        return back()->with('success', 'Post status updated!');
    }
    
    // ==================== SEO ANALYZER ====================
    
    public function seoAnalyzer()
    {
        $posts = DB::table('blog_posts')->orderBy('created_at', 'desc')->get();
        
        $analyzedPosts = [];
        $totalScore = 0;
        
        foreach ($posts as $post) {
            $checks = [];
            $score = 0;
            $maxScore = 0;
            
            // 1. Meta Title (max 15 pts)
            $maxScore += 15;
            $titleLen = mb_strlen($post->meta_title ?? '');
            if ($titleLen >= 50 && $titleLen <= 60) {
                $checks['title'] = ['status' => 'good', 'msg' => "Tốt ({$titleLen} ký tự)", 'pts' => 15];
                $score += 15;
            } elseif ($titleLen >= 30 && $titleLen < 70) {
                $checks['title'] = ['status' => 'ok', 'msg' => "Tạm ({$titleLen} ký tự)", 'pts' => 8];
                $score += 8;
            } elseif ($titleLen > 0) {
                $checks['title'] = ['status' => 'bad', 'msg' => "Quá " . ($titleLen < 30 ? 'ngắn' : 'dài') . " ({$titleLen})", 'pts' => 3];
                $score += 3;
            } else {
                $checks['title'] = ['status' => 'bad', 'msg' => 'Chưa có meta title', 'pts' => 0];
            }
            
            // 2. Meta Description (max 15 pts)
            $maxScore += 15;
            $descLen = mb_strlen($post->meta_description ?? '');
            if ($descLen >= 120 && $descLen <= 160) {
                $checks['description'] = ['status' => 'good', 'msg' => "Tốt ({$descLen} ký tự)", 'pts' => 15];
                $score += 15;
            } elseif ($descLen >= 80 && $descLen < 180) {
                $checks['description'] = ['status' => 'ok', 'msg' => "Tạm ({$descLen} ký tự)", 'pts' => 8];
                $score += 8;
            } elseif ($descLen > 0) {
                $checks['description'] = ['status' => 'bad', 'msg' => "Quá " . ($descLen < 80 ? 'ngắn' : 'dài') . " ({$descLen})", 'pts' => 3];
                $score += 3;
            } else {
                $checks['description'] = ['status' => 'bad', 'msg' => 'Chưa có meta description', 'pts' => 0];
            }
            
            // 3. Focus Keyword (max 15 pts)
            $maxScore += 15;
            $kw = $post->focus_keyword ?? '';
            if (!empty($kw)) {
                $inTitle = stripos($post->meta_title ?? $post->title, $kw) !== false;
                $inDesc = stripos($post->meta_description ?? '', $kw) !== false;
                $inContent = stripos($post->content ?? '', $kw) !== false;
                $kwScore = ($inTitle ? 5 : 0) + ($inDesc ? 5 : 0) + ($inContent ? 5 : 0);
                $where = [];
                if ($inTitle) $where[] = 'title';
                if ($inDesc) $where[] = 'desc';
                if ($inContent) $where[] = 'content';
                $checks['keyword'] = ['status' => $kwScore >= 10 ? 'good' : ($kwScore >= 5 ? 'ok' : 'bad'), 'msg' => 'Có trong: ' . (empty($where) ? 'không đâu' : implode(', ', $where)), 'pts' => $kwScore];
                $score += $kwScore;
            } else {
                $checks['keyword'] = ['status' => 'bad', 'msg' => 'Chưa đặt focus keyword', 'pts' => 0];
            }
            
            // 4. OG Image (max 10 pts)
            $maxScore += 10;
            $hasOgImage = !empty($post->og_image) || !empty($post->image);
            if ($hasOgImage) {
                $checks['og_image'] = ['status' => 'good', 'msg' => 'Có ảnh OG', 'pts' => 10];
                $score += 10;
            } else {
                $checks['og_image'] = ['status' => 'bad', 'msg' => 'Chưa có ảnh OG', 'pts' => 0];
            }
            
            // 5. Schema Markup (max 10 pts)
            $maxScore += 10;
            if (!empty($post->schema_json) || !empty($post->schema_type)) {
                $checks['schema'] = ['status' => 'good', 'msg' => $post->schema_type ?? 'Custom', 'pts' => 10];
                $score += 10;
            } else {
                $checks['schema'] = ['status' => 'bad', 'msg' => 'Chưa có schema', 'pts' => 0];
            }
            
            // 6. Content Length (max 15 pts)
            $maxScore += 15;
            $contentLen = mb_strlen(strip_tags($post->content ?? ''));
            if ($contentLen >= 1500) {
                $checks['content_length'] = ['status' => 'good', 'msg' => number_format($contentLen) . ' ký tự', 'pts' => 15];
                $score += 15;
            } elseif ($contentLen >= 800) {
                $checks['content_length'] = ['status' => 'ok', 'msg' => number_format($contentLen) . ' ký tự (nên >1500)', 'pts' => 8];
                $score += 8;
            } elseif ($contentLen > 0) {
                $checks['content_length'] = ['status' => 'bad', 'msg' => number_format($contentLen) . ' ký tự (quá ngắn)', 'pts' => 3];
                $score += 3;
            } else {
                $checks['content_length'] = ['status' => 'bad', 'msg' => 'Không có nội dung', 'pts' => 0];
            }
            
            // 7. Internal Links (max 10 pts)
            $maxScore += 10;
            preg_match_all('/<a\s[^>]*href=["\']([^"\']*)["\']/', $post->content ?? '', $links);
            $internalLinks = 0;
            foreach ($links[1] ?? [] as $link) {
                if (strpos($link, 'unlocktool.us') !== false || strpos($link, '/') === 0) {
                    $internalLinks++;
                }
            }
            if ($internalLinks >= 3) {
                $checks['internal_links'] = ['status' => 'good', 'msg' => "{$internalLinks} link nội bộ", 'pts' => 10];
                $score += 10;
            } elseif ($internalLinks >= 1) {
                $checks['internal_links'] = ['status' => 'ok', 'msg' => "{$internalLinks} link (nên ≥3)", 'pts' => 5];
                $score += 5;
            } else {
                $checks['internal_links'] = ['status' => 'bad', 'msg' => 'Không có link nội bộ', 'pts' => 0];
            }
            
            // 8. Heading Structure (max 10 pts)
            $maxScore += 10;
            preg_match_all('/<h[23][^>]*>/i', $post->content ?? '', $headings);
            $headingCount = count($headings[0] ?? []);
            if ($headingCount >= 3) {
                $checks['headings'] = ['status' => 'good', 'msg' => "{$headingCount} headings", 'pts' => 10];
                $score += 10;
            } elseif ($headingCount >= 1) {
                $checks['headings'] = ['status' => 'ok', 'msg' => "{$headingCount} heading (nên ≥3)", 'pts' => 5];
                $score += 5;
            } else {
                $checks['headings'] = ['status' => 'bad', 'msg' => 'Không có H2/H3', 'pts' => 0];
            }
            
            $pct = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
            $totalScore += $pct;
            
            $analyzedPosts[] = (object) [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status,
                'score' => $pct,
                'checks' => $checks,
            ];
        }
        
        $avgScore = count($analyzedPosts) > 0 ? round($totalScore / count($analyzedPosts)) : 0;
        $needsWork = collect($analyzedPosts)->where('score', '<', 60)->count();
        
        return view('admin.seo-analyzer.index', compact('analyzedPosts', 'avgScore', 'needsWork'));
    }
    
    // ==================== REPORTS ====================
    
    public function revenueReports(Request $request)
    {
        // Date range filter
        $range = $request->get('range', 'month');
        $from = $request->get('from');
        $to = $request->get('to');
        
        switch ($range) {
            case 'today':
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                break;
            case 'yesterday':
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            case 'custom':
                $startDate = $from ? \Carbon\Carbon::parse($from)->startOfDay() : now()->subDays(30)->startOfDay();
                $endDate = $to ? \Carbon\Carbon::parse($to)->endOfDay() : now()->endOfDay();
                break;
            default:
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
        }
        
        $baseQuery = DB::table('orders')
            ->whereIn('status', ['paid', 'completed'])
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate, $endDate]);
        
        // Summary stats
        $totalRevenue = (clone $baseQuery)->sum('amount');
        $totalOrders = (clone $baseQuery)->count();
        $avgPerOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;
        $uniqueIps = (clone $baseQuery)->distinct('ip_address')->count('ip_address');
        
        // Daily revenue for chart
        $dailyRevenue = (clone $baseQuery)
            ->select(
                DB::raw("DATE(paid_at) as date"),
                DB::raw("SUM(amount) as total"),
                DB::raw("COUNT(*) as count")
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        // Revenue by package (hours)
        $packageRevenue = (clone $baseQuery)
            ->select(
                'hours',
                DB::raw("COUNT(*) as order_count"),
                DB::raw("SUM(amount) as total")
            )
            ->groupBy('hours')
            ->orderByDesc('total')
            ->get()
            ->map(function($item) use ($totalRevenue) {
                $item->percentage = $totalRevenue > 0 ? round(($item->total / $totalRevenue) * 100, 1) : 0;
                $item->label = $item->hours . ' giờ';
                return $item;
            });
        
        // Top customers by IP
        $topCustomers = (clone $baseQuery)
            ->select(
                'ip_address',
                DB::raw("COUNT(*) as order_count"),
                DB::raw("SUM(amount) as total_spent")
            )
            ->groupBy('ip_address')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();
        
        return view('admin.reports.index', compact(
            'dailyRevenue', 'totalRevenue', 'totalOrders', 'avgPerOrder',
            'uniqueIps', 'packageRevenue', 'topCustomers',
            'range', 'startDate', 'endDate'
        ));
    }
    
    // ==================== SETTINGS ====================
    
    public function settings()
    {
        return view('admin.settings.index');
    }
    
    public function activityLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        $logContent = '';
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            // Last 5000 chars
            if (strlen($logContent) > 5000) {
                $logContent = substr($logContent, -5000);
            }
        }
        return view('admin.logs.index', compact('logContent'));
    }
    
    // ==================== BACKUP ====================
    
    public function backupPage()
    {
        $backupPath = storage_path('app/backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $files = glob($backupPath . '/*.sql');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('d/m/Y H:i:s', filemtime($file))
            ];
        }
        
        usort($backups, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        
        return view('admin.backup.index', compact('backups'));
    }
    
    public function createBackup()
    {
        $backupPath = storage_path('app/backups');
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $filepath = $backupPath . '/' . $filename;
        
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $key = "Tables_in_$dbName";
        
        $sql = "-- Backup created at " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $tableName = $table->$key;
            $createTable = DB::select("SHOW CREATE TABLE `$tableName`");
            $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
            
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                $columns = array_keys((array)$rows[0]);
                $sql .= "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = array_map(function($v) {
                        if ($v === null) return 'NULL';
                        return "'" . addslashes($v) . "'";
                    }, (array)$row);
                    $values[] = '(' . implode(', ', $rowValues) . ')';
                }
                
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        file_put_contents($filepath, $sql);
        
        return back()->with('success', "Đã tạo backup: $filename");
    }
    
    public function downloadBackup($filename)
    {
        $filepath = storage_path('app/backups/' . $filename);
        if (!file_exists($filepath)) {
            return back()->with('error', 'File không tồn tại!');
        }
        return response()->download($filepath);
    }
    
    public function deleteBackup($filename)
    {
        $filepath = storage_path('app/backups/' . $filename);
        if (file_exists($filepath)) {
            unlink($filepath);
            return back()->with('success', 'Đã xóa backup!');
        }
        return back()->with('error', 'File không tồn tại!');
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    // ==================== COUPONS ====================
    
    public function coupons(Request $request)
    {
        $query = DB::table('coupons');
        
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }
        
        $coupons = $query->orderBy('id', 'desc')->paginate(20);
        
        return view('admin.coupons.index', compact('coupons'));
    }
    
    public function saveCoupon(Request $request, $id = null)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
        ]);
        
        $data = [
            'code' => strtoupper($request->code),
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'max_discount_amount' => $request->max_discount_amount,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'expires_at' => $request->expires_at,
            'updated_at' => now(),
        ];
        
        if ($id) {
            DB::table('coupons')->where('id', $id)->update($data);
            return back()->with('success', 'Đã cập nhật mã giảm giá!');
        } else {
            $data['created_at'] = now();
            DB::table('coupons')->insert($data);
            return back()->with('success', 'Đã tạo mã giảm giá mới!');
        }
    }
    
    public function toggleCoupon($id)
    {
        $coupon = DB::table('coupons')->where('id', $id)->first();
        if ($coupon) {
            DB::table('coupons')->where('id', $id)->update([
                'is_active' => !$coupon->is_active,
                'updated_at' => now(),
            ]);
        }
        return back()->with('success', 'Đã cập nhật trạng thái!');
    }
    
    // ==================== EXPORT ====================
    
    public function exportPage()
    {
        return view('admin.export.index');
    }
    
    public function exportOrders(Request $request)
    {
        $query = Order::query();
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->period && $request->period !== 'all') {
            $dates = [
                'today' => now()->startOfDay(),
                'week' => now()->subDays(7),
                'month' => now()->subDays(30),
                'year' => now()->startOfYear(),
            ];
            if (isset($dates[$request->period])) {
                $query->where('created_at', '>=', $dates[$request->period]);
            }
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'orders_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Tracking Code', 'Service', 'Hours', 'Amount', 'Status', 'Created At']);
            
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->tracking_code,
                    $order->service_type ?? 'Unlocktool',
                    $order->hours,
                    $order->amount,
                    $order->status,
                    $order->created_at,
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    public function exportAccounts(Request $request)
    {
        $query = DB::table('accounts');
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('status')) {
            $query->where('is_available', $request->status);
        }
        
        $accounts = $query->orderBy('id', 'desc')->get();
        
        $filename = 'accounts_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($accounts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Type', 'Username', 'Password', 'Note', 'Available', 'Expires At']);
            
            foreach ($accounts as $account) {
                fputcsv($file, [
                    $account->id,
                    $account->type,
                    $account->username,
                    $account->password,
                    $account->note ?? '',
                    $account->is_available ? 'Yes' : 'No',
                    $account->expires_at ?? ''
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    public function importAccounts(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        fgetcsv($handle); // Skip header
        
        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 3) {
                DB::table('accounts')->insert([
                    'type' => $row[0] ?? 'Unlocktool',
                    'username' => $row[1],
                    'password' => $row[2],
                    'note' => $row[3] ?? null,
                    'is_available' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $imported++;
            }
        }
        
        fclose($handle);
        
        return back()->with('success', "Đã import $imported tài khoản thành công!");
    }
    
    // ==================== GLOBAL SEARCH ====================
    
    public function globalSearch(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Vui lòng nhập ít nhất 2 ký tự để tìm kiếm');
        }
        
        $results = collect();
        
        // Search orders
        try {
            $results['orders'] = Order::where('tracking_code', 'LIKE', "%$query%")
                ->orWhere('service_type', 'LIKE', "%$query%")
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            $results['orders'] = collect();
        }
        
        // Search accounts
        try {
            $results['accounts'] = DB::table('accounts')
                ->where('username', 'LIKE', "%$query%")
                ->orWhere('note', 'LIKE', "%$query%")
                ->orWhere('type', 'LIKE', "%$query%")
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            $results['accounts'] = collect();
        }
        
        // Search coupons
        try {
            $results['coupons'] = DB::table('coupons')
                ->where('code', 'LIKE', "%$query%")
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            $results['coupons'] = collect();
        }
        
        return view('admin.search.index', compact('query', 'results'));
    }
    
    // ==================== SYSTEM INFO ====================
    
    public function systemInfo()
    {
        // Database stats
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $key = "Tables_in_$dbName";
        
        $dbStats = [];
        foreach ($tables as $table) {
            $tableName = $table->$key;
            $count = DB::table($tableName)->count();
            $dbStats[$tableName] = $count;
        }
        
        // Disk info
        $totalDisk = disk_total_space('/');
        $freeDisk = disk_free_space('/');
        $usedDisk = $totalDisk - $freeDisk;
        $usedPercent = $totalDisk > 0 ? round(($usedDisk / $totalDisk) * 100, 1) : 0;
        
        $diskInfo = [
            'total' => $this->formatBytes($totalDisk),
            'used' => $this->formatBytes($usedDisk),
            'free' => $this->formatBytes($freeDisk),
            'used_percent' => $usedPercent,
        ];
        
        // Extensions
        $extensions = get_loaded_extensions();
        sort($extensions);
        
        return view('admin.system.info', compact('dbStats', 'diskInfo', 'extensions'));
    }
    
    public function clearCache()
    {
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');
        return back()->with('success', 'Đã xóa cache!');
    }
    
    public function clearViews()
    {
        \Artisan::call('view:clear');
        return back()->with('success', 'Đã xóa views cache!');
    }
    
    public function optimizeTables()
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $key = "Tables_in_$dbName";
        
        foreach ($tables as $table) {
            DB::statement("OPTIMIZE TABLE `{$table->$key}`");
        }
        
        return back()->with('success', 'Đã optimize tất cả tables!');
    }
    
    public function phpInfo()
    {
        phpinfo();
        exit;
    }
    
    
    // ==================== PASSWORD ROTATION ====================
    
    /**
     * Password Rotation page — Show accounts that need password change.
     */
    public function passwordRotation(Request $request)
    {
        $serviceColors = [
            'Unlocktool' => '#f97316',
        ];
        $typeLabels = [
            'Unlocktool' => 'UnlockTool',
        ];
        
        $currentType = $request->get('type');
        $soonThreshold = now()->addMinutes(45);
        
        $query = DB::table('accounts')
            ->leftJoin('orders', function ($join) {
                $join->on('accounts.id', '=', 'orders.account_id')
                     ->whereIn('orders.status', ['completed', 'expired'])
                     ->whereNotNull('orders.expires_at');
            })
            ->select(
                'accounts.id', 'accounts.username', 'accounts.password',
                'accounts.type', 'accounts.is_available',
                'accounts.new_password', 'accounts.needs_password_sync',
                'accounts.password_synced_at',
                DB::raw('MAX(orders.expires_at) as expired_at'),
                DB::raw('MAX(orders.tracking_code) as order_code')
            )
            // Only accounts WITHOUT notes
            ->where(function ($noteQ) {
                $noteQ->whereNull('accounts.note')->orWhere('accounts.note', '');
            })
            // CHỈ hiện acc đang thuê (is_available=0), ẩn acc đã chờ thuê (is_available=1)
            ->where('accounts.is_available', 0)
            ->groupBy(
                'accounts.id', 'accounts.username', 'accounts.password',
                'accounts.type', 'accounts.is_available', 'accounts.new_password',
                'accounts.needs_password_sync', 'accounts.password_synced_at'
            )
            // Chỉ hiện acc hết hạn hoặc còn dưới 45 phút
            ->having(DB::raw('MAX(orders.expires_at)'), '<', $soonThreshold);
        
        if ($currentType) {
            $query->where('accounts.type', $currentType);
        }
        
        $accounts = $query->orderBy('expired_at', 'asc')->get();
        
        // Auto-generate new password if not set
        foreach ($accounts as $account) {
            if (empty($account->new_password)) {
                $newPass = 'Unlock' . random_int(100, 999);
                DB::table('accounts')->where('id', $account->id)->update([
                    'new_password' => $newPass,
                    'needs_password_sync' => 1,
                ]);
                $account->new_password = $newPass;
            }
        }
        
        // Count per type (only types with accounts needing change)
        $allTypes = array_keys($typeLabels);
        $typeCounts = [];
        foreach ($allTypes as $type) {
            $c = $accounts->where('type', $type)->count();
            if ($c > 0) $typeCounts[$type] = $c;
        }
        
        // Stats
        $stats = [
            'needs_sync'    => $accounts->count(),
            'expiring_soon' => $accounts->filter(fn($a) => $a->expired_at && \Carbon\Carbon::parse($a->expired_at)->isFuture())->count(),
            'synced_today'  => DB::table('accounts')->whereNotNull('password_synced_at')->whereDate('password_synced_at', today())->count(),
            'total_accounts'=> DB::table('accounts')->count(),
        ];
        
        // Recently synced today
        $recentlySynced = DB::table('accounts')
            ->whereNotNull('password_synced_at')
            ->whereDate('password_synced_at', today())
            ->orderBy('password_synced_at', 'desc')
            ->limit(10)->get();
        
        return view('admin.accounts.password-rotation', compact(
            'accounts', 'stats', 'typeCounts', 'typeLabels', 'serviceColors', 'recentlySynced'
        ));
    }
    
    /**
     * AJAX: Mark account password as synced.
     */
    public function markPasswordSynced($id)
    {
        $account = DB::table('accounts')->where('id', $id)->first();
        if (!$account) {
            return response()->json(['success' => false, 'error' => 'Account không tồn tại!']);
        }
        
        // Check xem order mới nhất đã hết hạn chưa
        $latestOrder = DB::table('orders')
            ->where('account_id', $id)
            ->whereIn('status', ['completed', 'expired'])
            ->whereNotNull('expires_at')
            ->orderBy('expires_at', 'desc')
            ->first();
        
        $isExpired = !$latestOrder || \Carbon\Carbon::parse($latestOrder->expires_at)->isPast();
        
        $updateData = [
            // Nếu còn hạn → password_changed=1 để khách thấy "MK đã thay đổi" (pass mới không dùng được)
            // Nếu hết hạn → password_changed=0 (khách thấy "Phiên thuê đã kết thúc" anyway)
            'password_changed'    => $isExpired ? 0 : 1,
            'needs_password_sync' => 0,
            'password_synced_at'  => now(),
        ];
        
        // Chỉ chuyển "chờ thuê" nếu order đã hết hạn
        // Nếu còn thời gian → giữ is_available=0, chờ hết hạn tự nhiên
        if ($isExpired) {
            $updateData['is_available'] = 1;
        }
        
        if (!empty($account->new_password)) {
            $updateData['password'] = $account->new_password;
            $updateData['new_password'] = null;
        }
        
        DB::table('accounts')->where('id', $id)->update($updateData);
        $status = $isExpired ? 'chờ thuê' : 'giữ đang thuê (còn hạn)';
        Log::info("Password synced: #{$id} ({$account->username}) → {$status}");
        
        return response()->json(['success' => true, 'released' => $isExpired]);
    }
    
    /**
     * Generate new passwords in bulk.
     */
    public function generateAllPasswords(Request $request)
    {
        $query = DB::table('accounts')->where('needs_password_sync', 1);
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        $count = 0;
        foreach ($query->get() as $account) {
            DB::table('accounts')->where('id', $account->id)->update([
                'new_password' => 'Unlock' . random_int(100, 999),
            ]);
            $count++;
        }
        return back()->with('success', "Đã sinh password mới cho {$count} tài khoản!");
    }
    
    /**
     * Get count for sidebar badge (static method).
     */
    public static function getPasswordRotationCount(): int
    {
        try {
            $soonThreshold = now()->addMinutes(45);
            return DB::table('accounts')
                ->where(function ($noteQ) {
                    $noteQ->whereNull('note')->orWhere('note', '');
                })
                // CHỈ đếm acc đang thuê (is_available=0)
                ->where('is_available', 0)
                ->whereExists(function ($sub) use ($soonThreshold) {
                    $sub->select(DB::raw(1))->from('orders')
                        ->whereColumn('orders.account_id', 'accounts.id')
                        ->whereIn('orders.status', ['completed', 'expired'])
                        ->where('orders.expires_at', '<', $soonThreshold);
                })
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    // ==================== UNDERPAID ORDERS ====================
    
    public function underpaidOrders()
    {
        $orders = Order::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.underpaid.index', compact('orders'));
    }
}

