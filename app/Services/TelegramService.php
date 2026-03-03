<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Account;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Send a message via Telegram Bot API
     */
    public static function sendMessage(string $text): bool
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (empty($token) || empty($chatId)) {
            Log::warning('Telegram: bot_token or chat_id not configured');
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful() && $response->json('ok')) {
                return true;
            }

            Log::error('Telegram send failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Telegram error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify: New order (thuê tài khoản)
     */
    public static function notifyNewOrder(Order $order): void
    {
        $serviceName = $order->service_type ?? 'Unlocktool';
        $amount = number_format($order->amount, 0, ',', '.') . 'đ';
        $email = $order->customer_email ?? 'Không có';
        $user = 'Khách vãng lai';

        $text = "🛒 <b>ĐƠN HÀNG MỚI (UNLOCKTOOL.US)</b>\n\n"
            . "📋 Mã: <code>{$order->tracking_code}</code>\n"
            . "👤 Khách: {$user}\n"
            . "📧 Email: {$email}\n"
            . "🎮 Dịch vụ: {$serviceName}\n"
            . "⏰ Thời gian: {$order->hours}h\n"
            . "💰 Số tiền: <b>{$amount}</b>\n"
            . "📊 Trạng thái: {$order->status}";

        self::sendMessage($text);
    }

    /**
     * Notify: Account allocated successfully
     */
    public static function notifyAccountAllocated(Order $order, Account $account): void
    {
        $amount = number_format($order->amount, 0, ',', '.') . 'đ';
        $user = 'Khách vãng lai';
        $expiresAt = $order->expires_at ? $order->expires_at->format('d/m/Y H:i') : 'N/A';

        $text = "✅ <b>CẤP TÀI KHOẢN THÀNH CÔNG (UNLOCKTOOL.US)</b>\n\n"
            . "📋 Mã đơn: <code>{$order->tracking_code}</code>\n"
            . "👤 Khách: {$user}\n"
            . "🎮 Loại: {$account->type}\n"
            . "🔑 Tài khoản ID: #{$account->id}\n"
            . "💰 Số tiền: {$amount}\n"
            . "⏰ Hết hạn: {$expiresAt}";

        self::sendMessage($text);
    }
}
