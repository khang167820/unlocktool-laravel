@extends('layouts.app')

@section('title', 'Trạng thái đơn hàng - UnlockTool.us')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
/* ===== ORDER DETAIL — PREMIUM V3 ===== */
.od-page {
    min-height: 90vh;
    background: linear-gradient(160deg, #0a1628 0%, #162544 40%, #1a3a5c 100%);
    padding: 40px 16px;
    font-family: 'Inter', -apple-system, sans-serif;
    position: relative;
}
.od-page::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}

.od-container {
    max-width: 600px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* === Card === */
.od-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.05);
    overflow: hidden;
}

/* === Header with gradient === */
.od-header {
    background: linear-gradient(135deg, #1e3a5f, #2d5a87);
    padding: 28px 32px 24px;
    position: relative;
    overflow: hidden;
}
.od-header::after {
    content: '';
    position: absolute;
    top: -50%; right: -20%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
}

.od-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 12px;
    letter-spacing: -0.3px;
}

.od-status-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.od-status-label {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.6);
    font-weight: 500;
}

.od-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    border-radius: 24px;
    font-size: 0.78rem;
    font-weight: 700;
}
.od-badge.completed {
    background: rgba(46,125,50,0.2);
    color: #81c784;
    border: 1px solid rgba(129,199,132,0.3);
    box-shadow: 0 0 12px rgba(46,125,50,0.15);
}
.od-badge.paid {
    background: rgba(66,165,245,0.2);
    color: #90caf9;
    border: 1px solid rgba(144,202,249,0.3);
}
.od-badge.pending {
    background: rgba(255,167,38,0.2);
    color: #ffcc02;
    border: 1px solid rgba(255,204,2,0.3);
}

/* === Info Section === */
.od-info {
    padding: 4px 0;
}

.od-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 32px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.15s;
}
.od-row:hover { background: #fafbfc; }
.od-row:last-child { border-bottom: none; }

.od-row-label {
    font-size: 0.88rem;
    color: #64748b;
    font-weight: 600;
}

.od-row-value {
    font-size: 0.92rem;
    color: #1e293b;
    font-weight: 700;
    text-align: right;
}

.od-row-value.price {
    color: #dc2626;
    font-size: 1.05rem;
    font-weight: 800;
}

.od-row-value.mono {
    font-family: 'JetBrains Mono', 'Consolas', monospace;
    color: #334155;
    letter-spacing: 0.5px;
}

/* === Account Section (GREEN THEMED) === */
.od-account {
    margin: 4px 24px 0;
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1.5px solid #86efac;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(34,197,94,0.08);
}

.od-account-header {
    padding: 16px 22px;
    border-bottom: 1px solid #bbf7d0;
    font-size: 0.95rem;
    font-weight: 800;
    color: #166534;
    display: flex;
    align-items: center;
    gap: 8px;
}
.od-account-header i {
    color: #22c55e;
    font-size: 1.1rem;
}

.od-account-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 22px;
    border-bottom: 1px solid #dcfce7;
}
.od-account-row:last-child { border-bottom: none; }

.od-account-label {
    font-size: 0.85rem;
    color: #4ade80;
    font-weight: 600;
}

.od-account-value {
    font-size: 1rem;
    color: #14532d;
    font-weight: 800;
    font-family: 'JetBrains Mono', monospace;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: 0.3px;
}

.od-copy-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1.5px solid #86efac;
    background: #fff;
    color: #22c55e;
    font-size: 0.78rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.od-copy-btn:hover {
    background: #dcfce7;
    transform: scale(1.05);
}
.od-copy-btn.copied {
    background: #22c55e;
    color: #fff;
    border-color: #22c55e;
    animation: od-pop 0.3s ease;
}
@keyframes od-pop {
    50% { transform: scale(1.2); }
}

/* === Countdown (ORANGE/AMBER) === */
.od-countdown {
    margin: 12px 24px 0;
    padding: 16px 22px;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1.5px solid #fbbf24;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 12px rgba(245,158,11,0.1);
}

.od-countdown-label {
    font-size: 0.85rem;
    color: #b45309;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}
.od-countdown-label i { color: #f59e0b; }

.od-countdown-value {
    font-size: 1.5rem;
    font-weight: 900;
    color: #d97706;
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 2px;
    text-shadow: 0 1px 2px rgba(217,119,6,0.15);
}

.od-countdown-expired {
    font-size: 0.92rem;
    color: #dc2626;
    font-weight: 800;
}

/* === Waiting / Pending States === */
.od-state-box {
    margin: 12px 24px 0;
    padding: 28px 24px;
    text-align: center;
    border-radius: 16px;
}
.od-state-box.waiting {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1.5px solid #93c5fd;
}
.od-state-box.pending {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1.5px solid #fcd34d;
}

.od-state-icon { font-size: 2.5rem; margin-bottom: 12px; }

.od-state-box h4 {
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: 8px;
}
.od-state-box.waiting h4 { color: #1d4ed8; }
.od-state-box.pending h4 { color: #b45309; }

.od-state-box p {
    color: #64748b;
    font-size: 0.85rem;
    line-height: 1.7;
    margin-bottom: 16px;
}
.od-state-box p strong { color: #2563eb; }

.od-reload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 4px 14px rgba(37,99,235,0.3);
}
.od-reload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37,99,235,0.4);
}

.od-spinner {
    width: 34px;
    height: 34px;
    border: 3px solid #fde68a;
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: od-spin 0.8s linear infinite;
    margin: 18px auto 0;
}
@keyframes od-spin { to { transform: rotate(360deg); } }

/* === Actions === */
.od-actions {
    padding: 24px;
    display: flex;
    gap: 12px;
}

.od-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.25s;
    font-family: 'Inter', sans-serif;
    border: none;
    cursor: pointer;
}

.od-btn-outline {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    color: #475569;
}
.od-btn-outline:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #cbd5e1;
    text-decoration: none;
}

.od-btn-primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    box-shadow: 0 4px 14px rgba(37,99,235,0.3);
}
.od-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37,99,235,0.4);
    color: #fff;
    text-decoration: none;
}

/* === Not Found === */
.od-not-found {
    text-align: center;
    padding: 60px 20px;
}
.od-not-found i { font-size: 3rem; color: #cbd5e1; margin-bottom: 16px; }
.od-not-found h3 { color: #64748b; font-size: 1.1rem; margin-bottom: 24px; }

/* === Branding === */
.od-branding {
    text-align: center;
    margin-top: 24px;
    font-size: 0.72rem;
    color: rgba(255,255,255,0.25);
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

/* === Responsive === */
@media (max-width: 480px) {
    .od-page { padding: 20px 10px; }
    .od-header { padding: 22px 20px 18px; }
    .od-row { padding: 13px 20px; }
    .od-account { margin-left: 16px; margin-right: 16px; }
    .od-countdown { margin-left: 16px; margin-right: 16px; }
    .od-state-box { margin-left: 16px; margin-right: 16px; }
    .od-actions { padding: 20px 16px; flex-direction: column; }
    .od-title { font-size: 1.2rem; }
    .od-countdown-value { font-size: 1.2rem; }
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
            {{-- Gradient Header --}}
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

            {{-- Order Info --}}
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

            {{-- Account Info (Green themed) --}}
            @if($isCompleted)
                <div class="od-account">
                    <div class="od-account-header"><i class="fas fa-key"></i> Tài khoản đã cấp</div>
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
                    <div class="od-state-icon">✅</div>
                    <h4>Đã thanh toán thành công!</h4>
                    <p>Hệ thống đang cấp tài khoản. Trang sẽ tự động cập nhật.<br>Nếu quá 30 giây, liên hệ Zalo: <strong>0777333763</strong></p>
                    <button class="od-reload-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Tải lại trang</button>
                </div>

            @elseif($isPending)
                <div class="od-state-box pending">
                    <div class="od-state-icon">⏳</div>
                    <h4>Đang chờ thanh toán</h4>
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
