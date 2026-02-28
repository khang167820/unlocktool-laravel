<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Order;
use App\Models\Price;
use App\Helpers\OrderHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Get all prices
        $allPrices = Price::orderBy('hours', 'asc')->get();

        // Get accounts list - prioritize longest-waiting available accounts
        $accounts = Account::select('accounts.*', DB::raw('
            (SELECT MAX(o.expires_at) FROM orders o 
             WHERE o.account_id = accounts.id 
             AND o.status IN ("completed","paid")) as active_expires_at
        '))
        ->orderByRaw('CASE WHEN is_available = 1 THEN 0 ELSE 1 END')
        ->orderBy('available_since', 'asc')
        ->limit(100)
        ->get();

        // Count available accounts
        $availableCount = Account::where('is_available', 1)
            ->where(function ($q) {
                $q->whereNull('note')->orWhere('note', '');
            })
            ->count();

        // Recent orders (by IP, last 30 days)
        $userIp = OrderHelper::getClientIP();
        $recentOrders = Order::where('ip_address', $userIp)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('welcome', compact(
            'allPrices', 'accounts', 'availableCount', 'recentOrders'
        ));
    }
}
