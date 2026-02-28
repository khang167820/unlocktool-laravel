<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountAllocationService
{
    /**
     * Allocate an available account to a paid order.
     * Uses lockForUpdate() to prevent double-leasing during concurrent checkouts.
     */
    public static function allocateAccount(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            // Determine service type from order (default: Unlocktool)
            $type = 'Unlocktool';

            // Find available account with lock
            $account = Account::where('type', $type)
                ->where('is_available', 1)
                ->where(function ($q) {
                    $q->whereNull('note')->orWhere('note', '');
                })
                ->lockForUpdate()
                ->first();

            if (!$account) {
                Log::warning("ALLOCATION FAILED: No available accounts for type={$type}, order={$order->tracking_code}");
                return false;
            }

            // Calculate expiration
            $expiresAt = now()->addHours($order->hours);

            // Assign account to order
            $order->account_id = $account->id;
            $order->assigned_password = $account->password;
            $order->expires_at = $expiresAt;
            $order->status = 'completed';
            $order->completed_at = now();
            $order->save();

            // Mark account as rented
            $account->is_available = 0;
            $account->available_since = null;
            $account->save();

            Log::info("ALLOCATION SUCCESS: Account #{$account->id} -> Order {$order->tracking_code}, expires={$expiresAt}");
            return true;
        });
    }

    /**
     * Reclaim expired accounts back to the available pool.
     */
    public static function reclaimExpiredAccounts(): int
    {
        $expiredOrders = Order::where('status', 'completed')
            ->where('expires_at', '<', now())
            ->whereNotNull('account_id')
            ->get();

        $count = 0;
        foreach ($expiredOrders as $order) {
            $account = Account::find($order->account_id);
            if ($account && !$account->is_available) {
                $account->is_available = 1;
                $account->available_since = now();
                $account->save();

                $order->status = 'expired';
                $order->save();

                $count++;
            }
        }

        Log::info("RECLAIM: {$count} accounts returned to pool.");
        return $count;
    }
}
