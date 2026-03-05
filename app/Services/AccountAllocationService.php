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

            // Find available account with row lock (FIFO: oldest first)
            $account = Account::available()
                ->orderBy('id', 'asc')
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
                'completed_at' => now(),
            ]);

            DB::commit();

            Log::info("Account allocated: #{$account->id} ({$account->username}) → Order: {$order->tracking_code}");

            // Send Telegram notification (non-blocking)
            try {
                TelegramService::notifyAccountAllocated($order, $account);
            } catch (\Exception $e) {
                Log::error("Telegram failed for order {$order->tracking_code}: " . $e->getMessage());
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

                        // Only release if no admin note
                        if (empty($account->note)) {
                            $account->update([
                                'is_available' => true,
                                'rental_expires_at' => null,
                                'rental_order_code' => null,
                            ]);
                        }

                        $count++;
                        Log::info("Reclaimed account: #{$account->id} from order: {$order->tracking_code}" .
                            (!empty($account->note) ? ' (kept locked due to note)' : ''));
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
