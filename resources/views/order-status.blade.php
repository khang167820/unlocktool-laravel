@extends('layouts.app')

@section('title', 'Trạng thái đơn hàng - UnlockTool.us')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
/* ===== ORDER DETAIL PAGE ===== */
.od-page {
    min-height: 85vh;
    background: #f0f2f5;
    padding: 40px 16px;
    font-family: 'Inter', -apple-system, sans-serif;
}

.od-container {
    max-width: 640px;
    margin: 0 auto;
}

/* === Main Card === */
.od-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
    overflow: hidden;
}

/* === Header === */
.od-header {
    padding: 28px 32px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.od-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 10px;
}

.od-status-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.od-status-label {
    font-size: 0.85rem;
    color: #888;
    font-weight: 500;
}

.od-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
}

.od-badge.completed {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.od-badge.paid {
    background: #e3f2fd;
    color: #1565c0;
    border: 1px solid #90caf9;
}

.od-badge.pending {
    background: #fff8e1;
    color: #f57f17;
    border: 1px solid #ffe082;
}

/* === Info Rows === */
.od-info {
    padding: 0 32px;
}

.od-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid #f5f5f5;
}

.od-row:last-child {
    border-bottom: none;
}

.od-row-label {
    font-size: 0.88rem;
    color: #666;
    font-weight: 500;
}

.od-row-value {
    font-size: 0.92rem;
    color: #1a1a2e;
    font-weight: 700;
    text-align: right;
}

.od-row-value.price {
    color: #d32f2f;
    font-size: 1rem;
}

.od-row-value.mono {
    font-family: 'JetBrains Mono', 'Consolas', monospace;
    font-size: 0.88rem;
}

/* === Account Section === */
.od-account {
    margin: 16px 32px 0;
    background: #fafafa;
    border: 1px solid #eee;
    border-radius: 12px;
    overflow: hidden;
}

.od-account-header {
    padding: 14px 20px;
    border-bottom: 1px solid #eee;
    font-size: 0.92rem;
    font-weight: 800;
    color: #1a1a2e;
}

.od-account-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.od-account-row:last-child {
    border-bottom: none;
}

.od-account-label {
    font-size: 0.85rem;
    color: #888;
    font-weight: 500;
}

.od-account-value {
    font-size: 0.95rem;
    color: #1a1a2e;
    font-weight: 700;
    font-family: 'JetBrains Mono', 'Consolas', monospace;
    display: flex;
    align-items: center;
    gap: 8px;
}

.od-copy-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid #ddd;
    background: #fff;
    color: #888;
    font-size: 0.75rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.od-copy-btn:hover {
    background: #f0f0f0;
    color: #333;
}

.od-copy-btn.copied {
    background: #2e7d32;
    color: #fff;
    border-color: #2e7d32;
}

/* === Countdown === */
.od-countdown {
    margin: 16px 32px 0;
    padding: 14px 20px;
    background: #fff8e1;
    border: 1px solid #ffe082;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.od-countdown-label {
    font-size: 0.85rem;
    color: #f57f17;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.od-countdown-value {
    font-size: 1.3rem;
    font-weight: 900;
    color: #e65100;
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 1px;
}

.od-countdown-expired {
    font-size: 0.9rem;
    color: #d32f2f;
    font-weight: 700;
}

/* === Waiting / Pending States === */
.od-state-box {
    margin: 16px 32px 0;
    padding: 28px 24px;
    text-align: center;
    border-radius: 12px;
}

.od-state-box.waiting {
    background: #e3f2fd;
    border: 1px solid #90caf9;
}

.od-state-box.pending {
    background: #fff8e1;
    border: 1px solid #ffe082;
}

.od-state-box h4 {
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 8px;
}

.od-state-box.waiting h4 { color: #1565c0; }
.od-state-box.pending h4 { color: #f57f17; }

.od-state-box p {
    color: #666;
    font-size: 0.85rem;
    line-height: 1.6;
    margin-bottom: 16px;
}

.od-state-box p strong { color: #1565c0; }

.od-reload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: linear-gradient(135deg, #1976d2, #1565c0);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 8px rgba(21,101,192,0.2);
}

.od-reload-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(21,101,192,0.3);
}

.od-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #ffe082;
    border-top-color: #f57f17;
    border-radius: 50%;
    animation: od-spin 0.8s linear infinite;
    margin: 16px auto 0;
}

@keyframes od-spin { to { transform: rotate(360deg); } }

/* === Actions === */
.od-actions {
    padding: 24px 32px;
    display: flex;
    gap: 12px;
}

.od-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 13px 18px;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
    border: none;
    cursor: pointer;
}

.od-btn-outline {
    background: #fff;
    border: 1.5px solid #ddd;
    color: #555;
}

.od-btn-outline:hover {
    background: #f5f5f5;
    color: #333;
    text-decoration: none;
    border-color: #bbb;
}

.od-btn-primary {
    background: linear-gradient(135deg, #1976d2, #1565c0);
    color: #fff;
    box-shadow: 0 2px 8px rgba(21,101,192,0.2);
}

.od-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(21,101,192,0.3);
    color: #fff;
    text-decoration: none;
}

/* === Not Found === */
.od-not-found {
    text-align: center;
    padding: 60px 20px;
}

.od-not-found i {
    font-size: 3rem;
    color: #ccc;
    margin-bottom: 16px;
}

.od-not-found h3 {
    color: #888;
    font-size: 1.1rem;
    margin-bottom: 24px;
}

/* === Branding === */
.od-branding {
    text-align: center;
    margin-top: 20px;
    font-size: 0.72rem;
    color: #bbb;
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* === Responsive === */
@media (max-width: 480px) {
    .od-page { padding: 20px 12px; }
    .od-header { padding: 22px 20px 16px; }
    .od-info { padding: 0 20px; }
    .od-account { margin-left: 20px; margin-right: 20px; }
    .od-countdown { margin-left: 20px; margin-right: 20px; }
    .od-state-box { margin-left: 20px; margin-right: 20px; }
    .od-actions { padding: 20px; flex-direction: column; }
    .od-title { font-size: 1.15rem; }
}
</style>
@endsection

@section('content')
<div class="od-page">
<div class="od-container">

    @if(!$order)
        <div class="od-card">
            <div class="od-not-found">
                <i class="fas fa-search"></i>
                <h3>Không tìm thấy đơn hàng</h3>
                <a href="{{ route('home') }}" class="od-btn od-btn-primary"><i class="fas fa-home"></i> Về trang chủ</a>
            </div>
        </div>
    @else
        @php
            $isPending = $order->status === 'pending';
            $isPaid = in_array($order->status, ['paid', 'completed']) && !$order->account;
            $isCompleted = in_array($order->status, ['paid', 'completed']) && $order->account;
        @endphp

        <div class="od-card">
            {{-- Header --}}
            <div class="od-header">
                <div class="od-title">Đơn hàng {{ $order->tracking_code }}</div>
                <div class="od-status-row">
                    <span class="od-status-label">Trạng thái:</span>
                    <span class="od-badge {{ $isCompleted ? 'completed' : ($isPaid ? 'paid' : ($isPending ? 'pending' : 'paid')) }}">
                        @if($isCompleted)
                            <i class="fas fa-check-circle"></i> Hoàn thành
                        @elseif($isPaid)
                            <i class="fas fa-spinner fa-spin"></i> Đang cấp TK
                        @elseif($isPending)
                            <i class="fas fa-clock"></i> Chờ thanh toán
                        @else
                            <i class="fas fa-check"></i> Đã thanh toán
                        @endif
                    </span>
                </div>
            </div>

            {{-- Order Info Rows --}}
            <div class="od-info">
                <div class="od-row">
                    <span class="od-row-label">Mã tra cứu</span>
                    <span class="od-row-value mono">{{ $order->tracking_code }}</span>
                </div>
                <div class="od-row">
                    <span class="od-row-label">Gói thuê</span>
                    <span class="od-row-value">{{ $packageName }}</span>
                </div>
                <div class="od-row">
                    <span class="od-row-label">Loại dịch vụ</span>
                    <span class="od-row-value">Unlocktool</span>
                </div>
                <div class="od-row">
                    <span class="od-row-label">Số tiền</span>
                    <span class="od-row-value price">{{ $formattedAmount }}</span>
                </div>
                <div class="od-row">
                    <span class="od-row-label">Tạo lúc</span>
                    <span class="od-row-value">{{ $order->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @if($order->paid_at)
                <div class="od-row">
                    <span class="od-row-label">Thanh toán lúc</span>
                    <span class="od-row-value">{{ \Carbon\Carbon::parse($order->paid_at)->format('d/m/Y H:i:s') }}</span>
                </div>
                @endif
                @if($order->expires_at)
                <div class="od-row">
                    <span class="od-row-label">Hết hạn</span>
                    <span class="od-row-value">{{ \Carbon\Carbon::parse($order->expires_at)->format('d/m/Y H:i:s') }}</span>
                </div>
                @endif
            </div>

            {{-- Account Info --}}
            @if($isCompleted)
                <div class="od-account">
                    <div class="od-account-header">Tài khoản đã cấp</div>
                    <div class="od-account-row">
                        <span class="od-account-label">Loại dịch vụ</span>
                        <span class="od-account-value">Unlocktool</span>
                    </div>
                    <div class="od-account-row">
                        <span class="od-account-label">Username</span>
                        <div class="od-account-value">
                            <span>{{ $order->account->username }}</span>
                            <button class="od-copy-btn" onclick="copyText('{{ $order->account->username }}', this)"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="od-account-row">
                        <span class="od-account-label">Mật khẩu</span>
                        <div class="od-account-value">
                            <span>{{ $order->account->password }}</span>
                            <button class="od-copy-btn" onclick="copyText('{{ $order->account->password }}', this)"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                </div>

                {{-- Countdown --}}
                @if($timeRemaining && !$timeRemaining['expired'])
                    <div class="od-countdown">
                        <div class="od-countdown-label"><i class="fas fa-hourglass-half"></i> Thời gian còn lại</div>
                        <div class="od-countdown-value" id="countdown" data-expire="{{ $timeRemaining['timestamp'] }}">{{ $timeRemaining['text'] }}</div>
                    </div>
                @elseif($timeRemaining && $timeRemaining['expired'])
                    <div class="od-countdown">
                        <div class="od-countdown-label"><i class="fas fa-hourglass-end"></i> Trạng thái</div>
                        <div class="od-countdown-expired">Đã hết hạn</div>
                    </div>
                @endif

            @elseif($isPaid)
                <div class="od-state-box waiting">
                    <h4>✅ Đã thanh toán thành công!</h4>
                    <p>Hệ thống đang cấp tài khoản. Trang sẽ tự động cập nhật.<br>Nếu quá 30 giây, liên hệ Zalo: <strong>0777333763</strong></p>
                    <button class="od-reload-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Tải lại trang</button>
                </div>

            @elseif($isPending)
                <div class="od-state-box pending">
                    <h4>⏳ Đang chờ thanh toán</h4>
                    <p>Hệ thống sẽ tự động cập nhật khi nhận được thanh toán.</p>
                    <div class="od-spinner"></div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="od-actions">
                <a href="{{ route('home') }}" class="od-btn od-btn-outline"><i class="fas fa-arrow-left"></i> Về trang chủ</a>
                @if($isCompleted)
                    <a href="{{ route('home') }}" class="od-btn od-btn-primary"><i class="fas fa-check-circle"></i> Xem trang hoàn tất</a>
                @else
                    <a href="https://zalo.me/0777333763" target="_blank" class="od-btn od-btn-primary"><i class="fas fa-headset"></i> Hỗ trợ</a>
                @endif
            </div>
        </div>
    @endif

    <div class="od-branding">UNLOCKTOOL.US — THUÊ TỰ ĐỘNG 24/7</div>

</div>
</div>
@endsection

@section('scripts')
<script>
function updateCountdown() {
    var el = document.getElementById('countdown');
    if (!el) return;
    var expireMs = parseInt(el.getAttribute('data-expire')) * 1000;
    var diff = expireMs - Date.now();
    if (diff <= 0) {
        el.textContent = 'Đã hết hạn';
        el.className = 'od-countdown-expired';
        return;
    }
    var h = Math.floor(diff / 3600000);
    var m = Math.floor((diff % 3600000) / 60000);
    var s = Math.floor((diff % 60000) / 1000);
    el.textContent = (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}
setInterval(updateCountdown, 1000);

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fas fa-copy"></i>';
        }, 2000);
    });
}

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
</script>
@endsection
