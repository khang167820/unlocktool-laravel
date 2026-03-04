@extends('layouts.app')

@section('title', 'Trạng thái đơn hàng - UnlockTool.us')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh;">
<div class="overlay" style="max-width: 700px; margin: 20px;">
    <h2 class="mb-4 text-center">Trạng thái đơn hàng</h2>

    @if(!$order)
        <div class="alert alert-danger text-center">Không tìm thấy đơn hàng.</div>
        <a href="{{ route('home') }}" class="btn btn-primary btn-block">⬅ Quay lại trang chủ</a>
    @else
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Đơn hàng: <strong>{{ $order->tracking_code }}</strong></h5>
                    <span class="badge badge-{{ $order->status === 'completed' ? 'success' : ($order->status === 'paid' ? 'info' : 'warning') }}" style="font-size:1rem; padding: 8px 16px;">
                        {{ $order->status === 'completed' ? '✅ Hoàn thành' : ($order->status === 'paid' ? '✅ Đã thanh toán' : '⏳ Chờ thanh toán') }}
                    </span>
                </div>
                <hr>
                <p><strong>Gói thuê:</strong> {{ $packageName }}</p>
                <p><strong>Số tiền:</strong> {{ $formattedAmount }}</p>

                @if(in_array($order->status, ['paid', 'completed']) && $order->account)
                    <div class="alert alert-success mt-3">
                        <h5 class="mb-3">🔐 Thông tin tài khoản</h5>
                        <p><strong>Username:</strong> <span id="display-username">{{ $order->account->username }}</span>
                            <button class="btn btn-outline-secondary btn-sm copy-btn ml-2" data-copy="{{ $order->account->username }}">📋 Sao chép</button>
                        </p>
                        <p><strong>Password:</strong> <span id="display-password">{{ $order->assigned_password ?? $order->account->password }}</span>
                            <button class="btn btn-outline-secondary btn-sm copy-btn ml-2" data-copy="{{ $order->assigned_password ?? $order->account->password }}">📋 Sao chép</button>
                        </p>
                        @if($timeRemaining && !$timeRemaining['expired'])
                            <p><strong>⏱ Thời gian còn lại:</strong> <span id="countdown" data-expire="{{ $timeRemaining['timestamp'] }}">{{ $timeRemaining['text'] }}</span></p>
                        @elseif($timeRemaining && $timeRemaining['expired'])
                            <p><strong>⏱ Trạng thái:</strong> <span class="text-danger">Đã hết hạn</span></p>
                        @endif
                    </div>
                @elseif(in_array($order->status, ['paid', 'completed']) && !$order->account)
                    <div class="alert alert-info mt-3">
                        <h5>✅ Đã thanh toán thành công!</h5>
                        <p>Hệ thống đang cấp tài khoản cho bạn. Vui lòng đợi vài giây và <strong>tải lại trang</strong>.</p>
                        <p>Nếu đợi quá 5 phút chưa có tài khoản, vui lòng liên hệ admin qua Zalo: <strong>0777333763</strong></p>
                        <button class="btn btn-primary btn-sm" onclick="location.reload()">🔄 Tải lại trang</button>
                    </div>
                @elseif($order->status === 'pending')
                    <div class="alert alert-warning mt-3" id="pendingMessage">
                        <h5>⏳ Đang chờ thanh toán...</h5>
                        <p>Hệ thống sẽ tự động cập nhật khi nhận được thanh toán.</p>
                        <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                    </div>
                @endif
            </div>
        </div>

        <a href="{{ route('home') }}" class="btn btn-primary btn-block">⬅ Quay lại trang chủ</a>
    @endif
</div>
</div>
@endsection

@section('scripts')
<script>
// Countdown for active orders
function updateCountdown() {
    var el = document.getElementById('countdown');
    if (!el) return;
    var expireMs = parseInt(el.getAttribute('data-expire')) * 1000;
    var diff = expireMs - Date.now();
    if (diff <= 0) {
        el.textContent = 'Đã hết hạn';
        return;
    }
    var h = Math.floor(diff / 3600000);
    var m = Math.floor((diff % 3600000) / 60000);
    var s = Math.floor((diff % 60000) / 1000);
    el.textContent = h + 'h ' + m + 'm ' + s + 's';
}
setInterval(updateCountdown, 1000);

// Poll for payment/account status (if pending or paid without account)
@if(isset($order) && ($order->status === 'pending' || (in_array($order->status, ['paid', 'completed']) && !$order->account)))
var pollInterval = setInterval(function() {
    fetch('{{ route("order.check", $order->tracking_code) }}')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'paid' && data.username) {
                clearInterval(pollInterval);
                location.reload();
            } else if (data.status === 'paid' && '{{ $order->status }}' === 'pending') {
                clearInterval(pollInterval);
                location.reload();
            }
        })
        .catch(() => {});
}, 5000);
@endif

// Copy buttons
document.querySelectorAll('.copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var text = this.getAttribute('data-copy');
        navigator.clipboard.writeText(text).then(function() {
            alert('Đã sao chép: ' + text);
        });
    });
});
</script>
@endsection
