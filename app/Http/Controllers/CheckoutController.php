<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Price;
use App\Helpers\OrderHelper;
use App\Services\AccountAllocationService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Show checkout confirmation page.
     */
    public function show(Request $request)
    {
        $request->validate(['price_id' => 'required|integer']);

        $price = Price::findOrFail($request->price_id);

        return view('checkout', [
            'price' => $price,
            'packageName' => OrderHelper::displayPackageName($price->hours),
            'formattedPrice' => OrderHelper::formatMoney($price->price),
        ]);
    }

    /**
     * Create order and redirect to Pay2S payment.
     */
    public function create(Request $request)
    {
        $request->validate([
            'price_id' => 'required|integer',
            'recaptcha_token' => 'required|string',
        ]);

        $ipAddress = OrderHelper::getClientIP();

        // === SECURITY ===

        // 1. Blocked IPs
        $blockedIps = ['42.119.229.100'];
        if (in_array($ipAddress, $blockedIps)) {
            abort(403, 'Access denied.');
        }

        // 2. Verify reCAPTCHA v3
        $this->verifyRecaptcha($request->recaptcha_token, $ipAddress);

        // 3. Rate limiting with DB lock
        $this->checkRateLimit($ipAddress);

        // === CREATE ORDER ===

        $price = Price::findOrFail($request->price_id);
        $amount = (int) $price->price;

        if ($amount < 1000 || $amount > 10000000000) {
            abort(400, 'Số tiền thuê không hợp lệ.');
        }

        // 4. Check if there are available accounts BEFORE creating order
        $availableCount = DB::table('accounts')
            ->where('type', 'Unlocktool')
            ->where('is_available', 1)
            ->where(function ($q) {
                $q->whereNull('note')->orWhere('note', '');
            })
            ->count();

        if ($availableCount === 0) {
            return back()->with('error', 'Hiện tại đã hết tài khoản trống. Vui lòng quay lại sau.');
        }

        // Generate unique tracking code
        $trackingCode = $this->generateUniqueTrackingCode();

        // Insert order
        $orderId = DB::table('orders')->insertGetId([
            'tracking_code' => $trackingCode,
            'hours' => $price->hours,
            'amount' => $amount,
            'status' => 'pending',
            'created_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        DB::table('orders')->where('id', $orderId)->update(['order_code' => $orderId]);

        // Fetch created order to pass to Telegram
        $order = Order::find($orderId);
        if ($order) {
            try {
                TelegramService::notifyNewOrder($order);
            } catch (\Exception $e) {
                Log::error("Telegram notify failed for order {$orderId}: " . $e->getMessage());
            }
        }

        // === Pay2S API ===
        $payUrl = $this->createPay2sPayment($orderId, $trackingCode, $amount);

        if (!$payUrl) {
            DB::table('orders')->where('id', $orderId)->delete();
            return back()->with('error', 'Tạo link thanh toán thất bại. Vui lòng thử lại.');
        }

        return redirect($payUrl);
    }

    // === Private Methods ===

    private function verifyRecaptcha(string $token, string $ip): void
    {
        $secret = env('RECAPTCHA_SECRET_KEY');
        $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]));

        $result = json_decode($response, true);

        if (!$result['success'] || ($result['score'] ?? 0) < 0.5) {
            abort(403, 'Phát hiện hành vi bất thường. Vui lòng thử lại.');
        }
    }

    private function checkRateLimit(string $ip): void
    {
        DB::beginTransaction();
        try {
            // Max 3 orders per minute
            $oneMinAgo = now()->subMinute();
            $count1min = DB::table('orders')
                ->where('ip_address', $ip)
                ->where('created_at', '>=', $oneMinAgo)
                ->lockForUpdate()
                ->count();

            if ($count1min >= 3) {
                DB::rollBack();
                abort(429, 'Bạn chỉ được tạo tối đa 3 đơn hàng mỗi phút.');
            }

            // Max 10 pending orders per hour
            $oneHourAgo = now()->subHour();
            $pendingCount = DB::table('orders')
                ->where('ip_address', $ip)
                ->where('status', 'pending')
                ->where('created_at', '>=', $oneHourAgo)
                ->lockForUpdate()
                ->count();

            if ($pendingCount >= 10) {
                DB::rollBack();
                abort(429, 'Quá nhiều đơn hàng chờ thanh toán. Vui lòng đợi 60 phút.');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateUniqueTrackingCode(): string
    {
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = OrderHelper::generateTrackingCode();
            $exists = DB::table('orders')->where('tracking_code', $code)->exists();
            if (!$exists) return $code;
        }
        // Fallback
        return 'DH' . date('dm') . time() % 1000000;
    }

    private function createPay2sPayment(int $orderId, string $trackingCode, int $amount): ?string
    {
        $accessKey = env('PAY2S_ACCESS_KEY');
        $secretKey = env('PAY2S_SECRET_KEY');
        $partnerCode = env('PAY2S_PARTNER_CODE');
        $merchantName = env('PAY2S_MERCHANT_NAME');

        $returnUrl = url("/order-status?token={$trackingCode}&orderCode={$orderId}");
        $ipnUrl = url('/webhook/pay2s');
        $requestId = time() . '';

        $bankList = [['account_number' => '20867091', 'bank_id' => 'acb']];

        $rawHash = "accessKey={$accessKey}&amount={$amount}&bankAccounts=Array&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$trackingCode}&partnerCode={$partnerCode}&redirectUrl={$returnUrl}&requestId={$requestId}&requestType=pay2s";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = [
            'accessKey' => $accessKey,
            'partnerCode' => $partnerCode,
            'partnerName' => $merchantName,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $trackingCode,
            'orderType' => 'pay2s',
            'bankAccounts' => $bankList,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $ipnUrl,
            'requestType' => 'pay2s',
            'signature' => $signature,
        ];

        $ch = curl_init('https://payment.pay2s.vn/v1/gateway/api/create');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
        ]);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($result['status']) && $result['status'] == false) {
            Log::error('Pay2S create failed: ' . ($result['message'] ?? 'Unknown'), $result);
            return null;
        }

        return $result['payUrl'] ?? null;
    }
}
