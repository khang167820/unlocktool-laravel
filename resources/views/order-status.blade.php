@extends('layouts.app')

@section('title', 'Trạng thái đơn hàng - UnlockTool.us')

@section('head')
<style>
.os-page {
    min-height: 90vh;
    background: linear-gradient(145deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    padding: 40px 16px;
    position: relative;
    overflow: hidden;
}

.os-page::before {
    content: '';
    position: absolute;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
    top: -200px;
    right: -200px;
    border-radius: 50%;
}

.os-page::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(16,185,129,0.06) 0%, transparent 70%);
    bottom: -100px;
    left: -100px;
    border-radius: 50%;
}

.os-container {
    max-width: 560px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* === Progress Steps === */
.os-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 32px;
    padding: 0 20px;
}

.os-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.os-step-dot {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 700;
    transition: all 0.3s ease;
    border: 3px solid transparent;
}

.os-step-dot.active {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    box-shadow: 0 0 20px rgba(59,130,246,0.4);
}

.os-step-dot.done {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    box-shadow: 0 0 16px rgba(16,185,129,0.3);
}

.os-step-dot.waiting {
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.1);
}

.os-step-label {
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 8px;
    text-align: center;
    max-width: 80px;
}

.os-step-label.active { color: #93c5fd; }
.os-step-label.done { color: #6ee7b7; }
.os-step-label.waiting { color: rgba(255,255,255,0.3); }

.os-step-line {
    width: 60px;
    height: 3px;
    border-radius: 2px;
    margin: 0 4px;
    margin-bottom: 26px;
    transition: background 0.3s ease;
}

.os-step-line.done { background: linear-gradient(90deg, #10b981, #3b82f6); }
.os-step-line.waiting { background: rgba(255,255,255,0.1); }

/* === Main Card === */
.os-card {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}

.os-card-header {
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.os-order-code {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.5);
    font-weight: 500;
}

.os-order-code strong {
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
}

.os-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.3px;
}

.os-badge.completed {
    background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(5,150,105,0.15));
    color: #6ee7b7;
    border: 1px solid rgba(16,185,129,0.3);
}

.os-badge.paid {
    background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(37,99,235,0.15));
    color: #93c5fd;
    border: 1px solid rgba(59,130,246,0.3);
}

.os-badge.pending {
    background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(217,119,6,0.15));
    color: #fcd34d;
    border: 1px solid rgba(245,158,11,0.3);
}

/* Order Info */
.os-info {
    padding: 20px 28px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.os-info-item {
    padding: 14px 16px;
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
}

.os-info-label {
    font-size: 0.72rem;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 600;
    margin-bottom: 4px;
}

.os-info-value {
    font-size: 1rem;
    color: #fff;
    font-weight: 700;
}

/* === Account Credentials === */
.os-credentials {
    margin: 0 28px 20px;
    background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(5,150,105,0.04));
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 16px;
    overflow: hidden;
}

.os-cred-header {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(16,185,129,0.12);
    display: flex;
    align-items: center;
    gap: 10px;
}

.os-cred-header i {
    color: #10b981;
    font-size: 1.1rem;
}

.os-cred-header span {
    font-size: 0.92rem;
    font-weight: 700;
    color: #6ee7b7;
}

.os-cred-row {
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(16,185,129,0.08);
}

.os-cred-row:last-child { border-bottom: none; }

.os-cred-label {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.5);
    font-weight: 500;
}

.os-cred-value {
    font-size: 0.95rem;
    color: #fff;
    font-weight: 700;
    font-family: 'Fira Code', 'Consolas', monospace;
    display: flex;
    align-items: center;
    gap: 8px;
}

.os-copy-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid rgba(16,185,129,0.3);
    background: rgba(16,185,129,0.1);
    color: #6ee7b7;
    font-size: 0.78rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.os-copy-btn:hover {
    background: rgba(16,185,129,0.25);
    transform: scale(1.05);
}

.os-copy-btn.copied {
    background: #10b981;
    color: #fff;
    border-color: #10b981;
}

/* Countdown */
.os-countdown {
    margin: 0 28px 20px;
    padding: 16px 20px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.os-countdown-label {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.5);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.os-countdown-label i { color: #f59e0b; }

.os-countdown-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: #fbbf24;
    font-family: 'Fira Code', 'Consolas', monospace;
}

.os-countdown-expired {
    color: #f87171;
    font-weight: 700;
    font-size: 0.9rem;
}

/* === Waiting State === */
.os-waiting {
    margin: 0 28px 20px;
    padding: 28px 20px;
    text-align: center;
    background: linear-gradient(135deg, rgba(59,130,246,0.06), rgba(37,99,235,0.03));
    border: 1px solid rgba(59,130,246,0.15);
    border-radius: 16px;
}

.os-waiting-icon {
    font-size: 2.2rem;
    margin-bottom: 12px;
}

.os-waiting h4 {
    color: #93c5fd;
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.os-waiting p {
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
    line-height: 1.5;
    margin-bottom: 16px;
}

.os-waiting .os-reload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.os-waiting .os-reload-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(59,130,246,0.4);
}

/* === Pending State === */
.os-pending {
    margin: 0 28px 20px;
    padding: 28px 20px;
    text-align: center;
    background: linear-gradient(135deg, rgba(245,158,11,0.06), rgba(217,119,6,0.03));
    border: 1px solid rgba(245,158,11,0.15);
    border-radius: 16px;
}

.os-pending h4 {
    color: #fcd34d;
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.os-pending p {
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
}

.os-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(245,158,11,0.2);
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: os-spin 0.8s linear infinite;
    margin: 16px auto 0;
}

@keyframes os-spin {
    to { transform: rotate(360deg); }
}

/* === Footer Actions === */
.os-actions {
    padding: 20px 28px 28px;
    display: flex;
    gap: 12px;
}

.os-btn-home {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    color: rgba(255,255,255,0.7);
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.os-btn-home:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
    text-decoration: none;
}

.os-btn-contact {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.os-btn-contact:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(59,130,246,0.4);
    color: #fff;
    text-decoration: none;
}

/* Not Found */
.os-not-found {
    text-align: center;
    padding: 60px 20px;
}

.os-not-found i {
    font-size: 3rem;
    color: rgba(255,255,255,0.15);
    margin-bottom: 16px;
}

.os-not-found h3 {
    color: rgba(255,255,255,0.6);
    font-size: 1.1rem;
    margin-bottom: 20px;
}

/* Mobile */
@media (max-width: 480px) {
    .os-page { padding: 24px 12px; }
    .os-info { grid-template-columns: 1fr; gap: 10px; }
    .os-step-line { width: 36px; }
    .os-card-header { padding: 20px; }
    .os-info { padding: 16px 20px; }
    .os-credentials { margin: 0 20px 16px; }
    .os-countdown { margin: 0 20px 16px; }
    .os-waiting, .os-pending { margin: 0 20px 16px; }
    .os-actions { padding: 16px 20px 24px; flex-direction: column; }
}
</style>
@endsection

@section('content')
<div class="os-page">
<div class="os-container">

    {{-- Progress Steps --}}
    @if($order)
    @php
        $isPending = $order->status === 'pending';
        $isPaid = in_array($order->status, ['paid', 'completed']) && !$order->account;
        $isCompleted = in_array($order->status, ['paid', 'completed']) && $order->account;
        $isExpired = $order->status === 'expired';
    @endphp
    <div class="os-progress">
        <div class="os-step">
            <div class="os-step-dot done"><i class="fas fa-check"></i></div>
            <div class="os-step-label done">Đặt hàng</div>
        </div>
        <div class="os-step-line {{ $isPending ? 'waiting' : 'done' }}"></div>
        <div class="os-step">
            <div class="os-step-dot {{ $isPending ? 'active' : 'done' }}">
                @if($isPending)<i class="fas fa-clock"></i>@else<i class="fas fa-check"></i>@endif
            </div>
            <div class="os-step-label {{ $isPending ? 'active' : 'done' }}">Thanh toán</div>
        </div>
        <div class="os-step-line {{ $isCompleted ? 'done' : 'waiting' }}"></div>
        <div class="os-step">
            <div class="os-step-dot {{ $isCompleted ? 'done' : ($isPaid ? 'active' : 'waiting') }}">
                @if($isCompleted)<i class="fas fa-check"></i>@elseif($isPaid)<i class="fas fa-spinner fa-spin"></i>@else<i class="fas fa-key"></i>@endif
            </div>
            <div class="os-step-label {{ $isCompleted ? 'done' : ($isPaid ? 'active' : 'waiting') }}">Nhận TK</div>
        </div>
    </div>
    @endif

    @if(!$order)
        <div class="os-card">
            <div class="os-not-found">
                <i class="fas fa-search"></i>
                <h3>Không tìm thấy đơn hàng</h3>
                <a href="{{ route('home') }}" class="os-btn-contact"><i class="fas fa-home"></i> Về trang chủ</a>
            </div>
        </div>
    @else
        <div class="os-card">
            {{-- Header --}}
            <div class="os-card-header">
                <div class="os-order-code">
                    Đơn hàng <strong>{{ $order->tracking_code }}</strong>
                </div>
                <span class="os-badge {{ $order->status === 'completed' ? 'completed' : ($order->status === 'paid' ? 'paid' : ($order->status === 'expired' ? '' : 'pending')) }}">
                    {{ $order->status === 'completed' ? '✓ Hoàn thành' : ($order->status === 'paid' ? '✓ Đã thanh toán' : ($order->status === 'expired' ? 'Hết hạn' : '⏳ Chờ thanh toán')) }}
                </span>
            </div>

            {{-- Order Info --}}
            <div class="os-info">
                <div class="os-info-item">
                    <div class="os-info-label">Gói thuê</div>
                    <div class="os-info-value">{{ $packageName }}</div>
                </div>
                <div class="os-info-item">
                    <div class="os-info-label">Số tiền</div>
                    <div class="os-info-value">{{ $formattedAmount }}</div>
                </div>
            </div>

            {{-- Account Credentials (paid/completed with account) --}}
            @if(in_array($order->status, ['paid', 'completed']) && $order->account)
                <div class="os-credentials">
                    <div class="os-cred-header">
                        <i class="fas fa-key"></i>
                        <span>Thông tin tài khoản</span>
                    </div>
                    <div class="os-cred-row">
                        <span class="os-cred-label">Username</span>
                        <div class="os-cred-value">
                            <span id="display-username">{{ $order->account->username }}</span>
                            <button class="os-copy-btn" onclick="copyText('{{ $order->account->username }}', this)" title="Sao chép"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="os-cred-row">
                        <span class="os-cred-label">Password</span>
                        <div class="os-cred-value">
                            <span id="display-password">{{ $order->assigned_password ?? $order->account->password }}</span>
                            <button class="os-copy-btn" onclick="copyText('{{ $order->assigned_password ?? $order->account->password }}', this)" title="Sao chép"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                </div>

                {{-- Countdown --}}
                @if($timeRemaining && !$timeRemaining['expired'])
                    <div class="os-countdown">
                        <div class="os-countdown-label"><i class="fas fa-hourglass-half"></i> Thời gian còn lại</div>
                        <div class="os-countdown-value" id="countdown" data-expire="{{ $timeRemaining['timestamp'] }}">{{ $timeRemaining['text'] }}</div>
                    </div>
                @elseif($timeRemaining && $timeRemaining['expired'])
                    <div class="os-countdown">
                        <div class="os-countdown-label"><i class="fas fa-hourglass-end"></i> Trạng thái</div>
                        <div class="os-countdown-expired">Đã hết hạn</div>
                    </div>
                @endif

            {{-- Paid but no account --}}
            @elseif(in_array($order->status, ['paid', 'completed']) && !$order->account)
                <div class="os-waiting">
                    <div class="os-waiting-icon">✅</div>
                    <h4>Đã thanh toán thành công!</h4>
                    <p>Hệ thống đang cấp tài khoản. Trang sẽ tự động cập nhật.<br>Nếu quá 5 phút, liên hệ Zalo: <strong style="color:#93c5fd;">0777333763</strong></p>
                    <button class="os-reload-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Tải lại trang</button>
                </div>

            {{-- Pending --}}
            @elseif($order->status === 'pending')
                <div class="os-pending">
                    <h4>⏳ Đang chờ thanh toán</h4>
                    <p>Hệ thống sẽ tự động cập nhật khi nhận được thanh toán.</p>
                    <div class="os-spinner"></div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="os-actions">
                <a href="{{ route('home') }}" class="os-btn-home"><i class="fas fa-arrow-left"></i> Trang chủ</a>
                <a href="https://zalo.me/0777333763" target="_blank" class="os-btn-contact"><i class="fas fa-headset"></i> Hỗ trợ</a>
            </div>
        </div>
    @endif

</div>
</div>
@endsection

@section('scripts')
<script>
// Countdown
function updateCountdown() {
    var el = document.getElementById('countdown');
    if (!el) return;
    var expireMs = parseInt(el.getAttribute('data-expire')) * 1000;
    var diff = expireMs - Date.now();
    if (diff <= 0) {
        el.textContent = 'Đã hết hạn';
        el.classList.add('os-countdown-expired');
        return;
    }
    var h = Math.floor(diff / 3600000);
    var m = Math.floor((diff % 3600000) / 60000);
    var s = Math.floor((diff % 60000) / 1000);
    el.textContent = (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}
setInterval(updateCountdown, 1000);

// Copy to clipboard
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

// Poll for payment/account status
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
