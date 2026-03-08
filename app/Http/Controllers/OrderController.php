<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Helpers\OrderHelper;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Order status page (after payment redirect).
     */
    public function status(Request $request)
    {
        // Support both ?token= (from Pay2S redirect) and ?orderCode= (from search/old URLs)
        $trackingCode = $request->query('token');
        $orderCode = $request->query('orderCode');

        if (!$trackingCode && !$orderCode) {
            return redirect('/')->with('error', 'Mã đơn hàng không hợp lệ.');
        }

        $order = null;
        if ($trackingCode) {
            $order = Order::with('account')->where('tracking_code', $trackingCode)->first();
        }
        if (!$order && $orderCode) {
            $order = Order::with('account')
                ->where('tracking_code', $orderCode)
                ->orWhere('order_code', $orderCode)
                ->first();
        }

        if (!$order) {
            return redirect('/')->with('error', 'Không tìm thấy đơn hàng.');
        }

        $timeRemaining = null;
        if ($order->expires_at) {
            $expiresTime = $order->expires_at->timestamp;
            $now = time();
            if ($expiresTime > $now) {
                $remain = $expiresTime - $now;
                $h = floor($remain / 3600);
                $m = floor(($remain % 3600) / 60);
                $s = $remain % 60;
                $timeRemaining = [
                    'expired' => false,
                    'text' => "{$h}h {$m}m {$s}s",
                    'timestamp' => $expiresTime,
                    'seconds' => $remain,
                ];
            } else {
                $timeRemaining = ['expired' => true, 'text' => 'Đã hết hạn'];
            }
        }

        return view('order-status', [
            'order' => $order,
            'packageName' => OrderHelper::displayPackageName($order->hours),
            'formattedAmount' => OrderHelper::formatMoney($order->amount),
            'timeRemaining' => $timeRemaining,
        ]);
    }

    /**
     * AJAX endpoint: check if order has been paid.
     */
    public function checkPayment(string $code)
    {
        $order = Order::where('tracking_code', $code)->first();

        if (!$order) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if (in_array($order->status, ['paid', 'completed'])) {
            $data = ['status' => 'paid'];

            if ($order->account) {
                $isExpired = $order->expires_at && $order->expires_at->isPast();
                $canShow = !$order->account->is_available && !$order->account->password_changed && !$isExpired;

                if ($canShow) {
                    $data['username'] = $order->account->username;
                    $data['password'] = $order->account->password;
                } else {
                    $data['expired'] = true;
                }
            }

            return response()->json($data);
        }

        return response()->json(['status' => $order->status]);
    }
}
