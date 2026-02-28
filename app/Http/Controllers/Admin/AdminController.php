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
        $query = Order::orderBy('created_at', 'desc');
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $query->where('tracking_code', 'like', '%' . $request->search . '%');
        }
        
        $orders = $query->paginate(20)->withQueryString();
        
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
            
            $success = \App\Services\AccountAllocationService::allocateAccount($order);
            
            if ($success) {
                return back()->with('success', 'Payment confirmed & account allocated!');
            } else {
                return back()->with('warning', 'Payment confirmed but no available accounts!');
            }
        }
        
        return back()->with('success', 'Order status updated!');
    }
    
    // ==================== ACCOUNTS ====================
    
    public function accounts(Request $request)
    {
        // Advanced query with sorting logic
        $latestOrders = DB::table('orders')
            ->select('account_id', DB::raw('MAX(expires_at) as latest_expires_at'))
            ->where('status', 'completed')
            ->groupBy('account_id');

        $accounts = DB::table('accounts')
            ->leftJoinSub($latestOrders, 'latest_orders', function ($join) {
                $join->on('accounts.id', '=', 'latest_orders.account_id');
            })
            ->select('accounts.*', 'latest_orders.latest_expires_at as sorting_expires_at')
            ->orderByRaw("
                CASE 
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at < NOW() AND (accounts.note IS NULL OR accounts.note = '') THEN 1
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at >= NOW() AND (accounts.note IS NULL OR accounts.note = '') THEN 2
                    WHEN accounts.is_available = 0 AND (accounts.note IS NULL OR accounts.note = '') THEN 3
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at < NOW() AND accounts.note IS NOT NULL AND accounts.note != '' THEN 4
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at >= NOW() AND accounts.note IS NOT NULL AND accounts.note != '' THEN 5
                    WHEN accounts.is_available = 0 AND accounts.note IS NOT NULL AND accounts.note != '' THEN 6
                    ELSE 7
                END ASC
            ")
            ->orderByRaw("
                CASE 
                    WHEN accounts.is_available = 0 AND latest_orders.latest_expires_at >= NOW() THEN latest_orders.latest_expires_at
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
            $rentalInfo = DB::table('orders')
                ->whereIn('account_id', $rentedAccountIds)
                ->where('status', 'completed')
                ->whereNotNull('expires_at')
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
        ]);
        
        DB::table('accounts')->insert([
            'username' => $data['username'],
            'password' => $data['password'],
            'type' => 'Unlocktool',
            'is_available' => 1,
            'note' => $data['note'] ?? null,
        ]);
        
        return back()->with('success', 'Account added!');
    }
    
    public function toggleAccount($id)
    {
        $account = DB::table('accounts')->where('id', $id)->first();
        if (!$account) return redirect()->route('admin.accounts')->with('error', 'Account not found!');
        
        $status = request()->input('status');
        if ($status === 'available') {
            $newAvailable = true;
        } elseif ($status === 'renting') {
            $newAvailable = false;
        } else {
            $newAvailable = !$account->is_available;
        }
        
        $updateData = ['is_available' => $newAvailable ? 1 : 0];
        if ($newAvailable) {
            $updateData['note'] = null;
            $updateData['note_date'] = null;
        }
        
        DB::table('accounts')->where('id', $id)->update($updateData);
        
        return redirect()->route('admin.accounts')->with('success', 'Account status updated!');
    }
    
    public function updateAccount(Request $request, $id)
    {
        $data = [];
        if ($request->has('username')) $data['username'] = $request->username;
        if ($request->has('password')) $data['password'] = $request->password;
        if ($request->has('note')) $data['note'] = $request->note;
        if ($request->has('note_date')) $data['note_date'] = $request->note_date;
        
        if ($request->has('note') && !empty($request->note)) {
            $data['is_available'] = 0;
        }
        
        if (!empty($data)) {
            DB::table('accounts')->where('id', $id)->update($data);
        }
        
        return redirect()->route('admin.accounts')->with('success', 'Account updated!');
    }
    
    public function deleteAccount($id)
    {
        DB::table('accounts')->where('id', $id)->delete();
        return redirect()->route('admin.accounts')->with('success', 'Account deleted!');
    }
    
    public function editAccount($id)
    {
        $account = DB::table('accounts')->where('id', $id)->first();
        if (!$account) return redirect()->route('admin.accounts')->with('error', 'Account not found!');
        return view('admin.accounts.edit', compact('account'));
    }
    
    public function batchToggleAccounts(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return redirect()->route('admin.accounts')->with('error', 'No accounts selected!');
        }
        
        $affected = DB::table('accounts')
            ->whereIn('id', $ids)
            ->update([
                'is_available' => 1,
                'note' => null,
                'note_date' => null,
            ]);
        
        return redirect()->route('admin.accounts')->with('success', "Set {$affected} accounts to available!");
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
    
    // ==================== REPORTS ====================
    
    public function revenueReports()
    {
        // Daily revenue for last 30 days
        $dailyRevenue = DB::table('orders')
            ->whereIn('status', ['paid', 'completed'])
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays(30))
            ->select(
                DB::raw("DATE(paid_at) as date"),
                DB::raw("SUM(amount) as total"),
                DB::raw("COUNT(*) as count")
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        
        return view('admin.reports.index', compact('dailyRevenue'));
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
}
