<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AccountAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Pay2S webhook (IPN) callback.
     */
    public function handlePay2s(Request $request)
    {
        Log::channel('single')->info('PAY2S WEBHOOK RECEIVED', [
            'body' => $request->all(),
        ]);

        $data = $request->all();

        // === 1. Verify Signature ===
        if (!$this->verifySignature($data)) {
            Log::warning('PAY2S: Invalid signature');
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        $resultCode = $data['resultCode'] ?? -1;
        $orderId = $data['orderId'] ?? null;
        $orderInfo = $data['orderInfo'] ?? '';
        $amount = $data['amount'] ?? 0;

        // === 2. Check result code ===
        if (!in_array($resultCode, ['0', 0, '00'], true)) {
            Log::info("PAY2S: Non-success resultCode={$resultCode}");
            return response()->json(['status' => 'ignored']);
        }

        // === 3. Find pending order ===
        $order = Order::where('status', 'pending')
            ->where(function ($q) use ($orderId, $orderInfo) {
                $q->where('order_code', $orderId)
                  ->orWhere('tracking_code', $orderInfo);
            })
            ->first();

        if (!$order) {
            // Already processed?
            $existing = Order::where('order_code', $orderId)
                ->orWhere('tracking_code', $orderInfo)
                ->first();

            if ($existing && in_array($existing->status, ['paid', 'completed'])) {
                return response()->json(['status' => 'already_paid']);
            }

            Log::error("PAY2S: Order not found - orderId={$orderId}, orderInfo={$orderInfo}");
            return response()->json(['status' => 'order_not_found'], 404);
        }

        // === 4. Verify amount ===
        if ((int) $amount !== (int) $order->amount) {
            Log::warning("PAY2S: Amount mismatch - expected={$order->amount}, received={$amount}");
        }

        // === 5. Allocate account ===
        $result = AccountAllocationService::allocateAccount($order);

        if ($result['success']) {
            Log::info("PAY2S: Order {$order->tracking_code} → completed with account #{$result['account']->id}");
        } else {
            // No account available → mark paid, admin assigns manually
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            Log::warning("PAY2S: Order {$order->tracking_code} paid but allocation failed: " . ($result['error'] ?? 'unknown'));
        }

        return response()->json(['success' => true, 'message' => 'OK']);
    }

    /**
     * Verify Pay2S webhook signature (HMAC SHA256).
     */
    private function verifySignature(array $data): bool
    {
        $accessKey = config('services.pay2s.access_key');
        $secretKey = config('services.pay2s.secret_key');

        $fields = [
            'accessKey' => $accessKey,
            'amount' => $data['amount'] ?? '',
            'extraData' => $data['extraData'] ?? '',
            'message' => $data['message'] ?? '',
            'orderId' => $data['orderId'] ?? '',
            'orderInfo' => $data['orderInfo'] ?? '',
            'orderType' => $data['orderType'] ?? '',
            'partnerCode' => $data['partnerCode'] ?? '',
            'payType' => $data['payType'] ?? '',
            'requestId' => $data['requestId'] ?? '',
            'responseTime' => $data['responseTime'] ?? '',
            'resultCode' => $data['resultCode'] ?? '',
            'transId' => $data['transId'] ?? '',
        ];

        $rawHash = collect($fields)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('&');

        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        return hash_equals($signature, $data['m2signature'] ?? '');
    }
}
