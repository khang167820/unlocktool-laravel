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
    public static function allocateAccount(Order $order, int $retryCount = 0): array
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

            // Safety: double-check no active rental exists for this account
            $hasActiveRental = Order::where('account_id', $account->id)
                ->whereIn('status', ['paid', 'completed'])
                ->whereNotNull('expires_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($hasActiveRental) {
                $account->update(['is_available' => false]);
                DB::commit();
                Log::warning("Account #{$account->id} has active rental but was marked available. Fixed.");
                if ($retryCount < 3) {
                    return static::allocateAccount($order, $retryCount + 1);
                }
                return ['success' => false, 'account' => null, 'error' => 'Không tìm được tài khoản trống.'];
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
            $rentingAccounts = Account::where('is_available', false)->get();

            foreach ($rentingAccounts as $account) {
                try {
                    // Skip if account has ANY active (non-expired) order
                    $hasActiveOrder = Order::where('account_id', $account->id)
                        ->whereIn('status', ['paid', 'completed'])
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '>', now())
                        ->exists();

                    if ($hasActiveOrder) continue;

                    // Check at least one expired order exists
                    $hasExpiredOrder = Order::where('account_id', $account->id)
                        ->whereIn('status', ['paid', 'completed'])
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<', now())
                        ->exists();

                    if (!$hasExpiredOrder) continue;

                    DB::beginTransaction();

                    $account = Account::where('id', $account->id)
                        ->lockForUpdate()
                        ->first();

                    if ($account && !$account->is_available) {
                        if (empty($account->note) && $account->password_changed) {
                            Order::where('account_id', $account->id)
                                ->where('status', 'completed')
                                ->whereNotNull('expires_at')
                                ->where('expires_at', '<', now())
                                ->update(['status' => 'expired']);

                            $account->update([
                                'is_available' => true,
                                'rental_expires_at' => null,
                                'rental_order_code' => null,
                                'password_changed' => 0,
                            ]);
                            $count++;
                            Log::info("Reclaimed account: #{$account->id} ({$account->username})");
                        } else {
                            $reason = [];
                            if (!empty($account->note)) $reason[] = 'has note';
                            if (!$account->password_changed) $reason[] = 'password not changed';
                            Log::info("Skipped reclaim for account #{$account->id}: " . implode(', ', $reason));
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Failed to reclaim account #{$account->id}: " . $e->getMessage());
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
