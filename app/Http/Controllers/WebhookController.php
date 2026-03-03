<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AccountAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Pay2S webhook callback.
     */
    public function handlePay2s(Request $request)
    {
        // Log raw payload
        Log::channel('single')->info('PAY2S WEBHOOK RECEIVED', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        $data = $request->all();

        // Extract order info
        $orderId = $data['orderId'] ?? null;
        $orderInfo = $data['orderInfo'] ?? '';
        $amount = $data['amount'] ?? 0;
        $resultCode = $data['resultCode'] ?? -1;

        // resultCode == 0 means success
        if ($resultCode != 0) {
            Log::info("PAY2S: Non-success resultCode={$resultCode}");
            return response()->json(['status' => 'ignored']);
        }

        // Find order by tracking_code (orderInfo)
        $order = Order::where('tracking_code', $orderInfo)->first();

        if (!$order) {
            // Try by orderId
            $order = Order::find($orderId);
        }

        if (!$order) {
            Log::error("PAY2S: Order not found - orderId={$orderId}, orderInfo={$orderInfo}");
            return response()->json(['status' => 'order_not_found'], 404);
        }

        // Verify amount
        if ((int)$amount !== (int)$order->amount) {
            Log::warning("PAY2S: Amount mismatch - expected={$order->amount}, received={$amount}");
        }

        // Already paid?
        if (in_array($order->status, ['paid', 'completed'])) {
            Log::info("PAY2S: Order {$order->tracking_code} already paid.");
            return response()->json(['status' => 'already_paid']);
        }

        // Update order status to paid
        $order->status = 'paid';
        $order->paid_at = now();
        $order->save();

        Log::info("PAY2S: Order {$order->tracking_code} marked as PAID.");

        // Allocate account automatically
        $allocationResult = AccountAllocationService::allocateAccount($order);

        if ($allocationResult['success']) {
            Log::info("PAY2S: Account allocated for order {$order->tracking_code}");
        } else {
            Log::warning("PAY2S: Account allocation FAILED for order {$order->tracking_code}: " . ($allocationResult['error'] ?? 'unknown'));
        }

        return response()->json(['status' => 'success']);
    }
}
