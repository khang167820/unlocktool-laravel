<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;

class AccountAllocationService
{
    /**
     * Allocate an available account to a paid order.
     *
     * @param Order $order The order to allocate account for
     * @return array{success: bool, account: ?Account, error: ?string}
     */
    public static function allocateAccount(Order $order): array
    {
        try {
            DB::beginTransaction();

            // Find available account with longest idle time (oldest expired order first)
            $account = Account::available()
                ->leftJoin(
                    DB::raw('(SELECT account_id, MAX(expires_at) as last_expires FROM orders WHERE status IN ("paid","completed") GROUP BY account_id) as lo'),
                    'accounts.id', '=', 'lo.account_id'
                )
                ->orderByRaw('COALESCE(lo.last_expires, accounts.created_at) ASC')
                ->select('accounts.*')
                ->lockForUpdate()
                ->first();

            if (!$account) {
                DB::rollBack();
                Log::warning("No available account for order: {$order->tracking_code}");
                return [
                    'success' => false,
                    'account' => null,
                    'error' => 'Không còn tài khoản trống. Vui lòng liên hệ admin.',
                ];
            }

            // Calculate expiry time
            $hours = $order->hours ?? 0;
            $expiresAt = $hours > 0 ? now()->addHours($hours) : null;

            // Mark account as rented
            $account->update([
                'is_available' => false,
                'rental_expires_at' => $expiresAt,
                'rental_order_code' => $order->tracking_code,
            ]);

            // Update order with account info → completed
            $order->update([
                'account_id' => $account->id,
                'status' => 'completed',
                'paid_at' => $order->paid_at ?? now(),
                'expires_at' => $expiresAt,
            ]);

            DB::commit();

            Log::info("Account allocated: #{$account->id} ({$account->username}) → Order: {$order->tracking_code}");

            // Send Telegram notification (non-blocking)
            try {
                TelegramService::notifyAccountAllocated($order, $account);
            } catch (\Exception $e) {
                Log::error("Telegram failed for order {$order->tracking_code}: " . $e->getMessage());
            }

            // Send email to customer if email provided (non-blocking)
            if (!empty($order->customer_email)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($order->customer_email)
                        ->send(new \App\Mail\OrderCompleted($order));
                    Log::info("Email sent to {$order->customer_email} for order {$order->tracking_code}");
                } catch (\Exception $e) {
                    Log::error("Email failed for order {$order->tracking_code}: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'account' => $account,
                'error' => null,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Account allocation failed for order {$order->tracking_code}: " . $e->getMessage());
            return [
                'success' => false,
                'account' => null,
                'error' => 'Lỗi hệ thống khi cấp tài khoản: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reclaim expired accounts.
     * Call this from a scheduled command or cron.
     *
     * @return int Number of accounts reclaimed
     */
    public static function reclaimExpiredAccounts(): int
    {
        $count = 0;

        try {
            $expiredOrders = Order::where('status', 'completed')
                ->whereNotNull('account_id')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->get();

            foreach ($expiredOrders as $order) {
                try {
                    DB::beginTransaction();

                    $account = Account::where('id', $order->account_id)
                        ->lockForUpdate()
                        ->first();

                    if ($account && !$account->is_available) {
                        $order->update(['status' => 'expired']);

                        // Only auto-release if: no note AND password already changed
                        if (empty($account->note) && $account->password_changed) {
                            $account->update([
                                'is_available' => true,
                                'rental_expires_at' => null,
                                'rental_order_code' => null,
                                'password_changed' => 0,
                            ]);
                        }

                        $reason = [];
                        if (!empty($account->note)) $reason[] = 'has note';
                        if (!$account->password_changed) $reason[] = 'password not changed';
                        $kept = !empty($reason) ? ' (kept locked: ' . implode(', ', $reason) . ')' : '';
                        
                        $count++;
                        Log::info("Reclaimed account: #{$account->id} from order: {$order->tracking_code}{$kept}");
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to reclaim account for order {$order->tracking_code}: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error("Reclaim expired accounts error: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * Get account statistics.
     */
    public static function getStats(): array
    {
        return [
            'total' => Account::count(),
            'available' => Account::where('is_available', true)->count(),
            'renting' => Account::where('is_available', false)->count(),
        ];
    }
}
