@extends('layouts.app')

@section('title', 'Trạng thái đơn hàng - UnlockTool.us')

@section('head')
<style>
.os-page {
    min-height: 85vh;
    background: #f0f2f5;
    padding: 32px 16px;
}

.os-container {
    max-width: 560px;
    margin: 0 auto;
}

/* === Progress Steps === */
.os-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 28px;
    padding: 0 20px;
}

.os-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 1;
}

.os-step-dot {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 700;
    transition: all 0.3s ease;
}

.os-step-dot.done {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}

.os-step-dot.active {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
    animation: os-pulse 2s infinite;
}

.os-step-dot.waiting {
    background: #e5e7eb;
    color: #9ca3af;
}

@keyframes os-pulse {
    0%, 100% { box-shadow: 0 4px 12px rgba(59,130,246,0.3); }
    50% { box-shadow: 0 4px 20px rgba(59,130,246,0.5); }
}

.os-step-label {
    font-size: 0.72rem;
    font-weight: 700;
    margin-top: 8px;
    text-align: center;
}

.os-step-label.done { color: #059669; }
.os-step-label.active { color: #2563eb; }
.os-step-label.waiting { color: #9ca3af; }

.os-step-line {
    width: 56px;
    height: 3px;
    border-radius: 2px;
    margin: 0 6px;
    margin-bottom: 26px;
}

.os-step-line.done { background: linear-gradient(90deg, #10b981, #10b981); }
.os-step-line.waiting { background: #e5e7eb; }

/* === Card === */
.os-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    overflow: hidden;
}

.os-card-header {
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f3f4f6;
}

.os-order-code {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
}

.os-order-code strong {
    color: #1f2937;
    font-size: 1.05rem;
    font-weight: 800;
}

.os-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
}

.os-badge.completed {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}

.os-badge.paid {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.os-badge.pending {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
}

/* Order Info */
.os-info {
    padding: 16px 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.os-info-item {
    padding: 14px 16px;
    background: #f9fafb;
    border-radius: 10px;
    border: 1px solid #f3f4f6;
}

.os-info-label {
    font-size: 0.72rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 600;
    margin-bottom: 4px;
}

.os-info-value {
    font-size: 1.05rem;
    color: #1f2937;
    font-weight: 800;
}

/* === Credentials === */
.os-credentials {
    margin: 4px 24px 16px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 14px;
    overflow: hidden;
}

.os-cred-header {
    padding: 14px 18px;
    border-bottom: 1px solid #a7f3d0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.os-cred-header i { color: #059669; font-size: 1rem; }

.os-cred-header span {
    font-size: 0.9rem;
    font-weight: 700;
    color: #065f46;
}

.os-cred-row {
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #d1fae5;
}

.os-cred-row:last-child { border-bottom: none; }

.os-cred-label {
    font-size: 0.82rem;
    color: #6b7280;
    font-weight: 500;
}

.os-cred-value {
    font-size: 0.95rem;
    color: #1f2937;
    font-weight: 700;
    font-family: 'Fira Code', 'Consolas', monospace;
    display: flex;
    align-items: center;
    gap: 8px;
}

.os-copy-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1px solid #a7f3d0;
    background: #fff;
    color: #059669;
    font-size: 0.78rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.os-copy-btn:hover { background: #d1fae5; }

.os-copy-btn.copied {
    background: #059669;
    color: #fff;
    border-color: #059669;
}

/* Countdown */
.os-countdown {
    margin: 0 24px 16px;
    padding: 14px 18px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.os-countdown-label {
    font-size: 0.82rem;
    color: #92400e;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.os-countdown-label i { color: #d97706; }

.os-countdown-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: #d97706;
    font-family: 'Fira Code', 'Consolas', monospace;
}

.os-countdown-expired { color: #dc2626; font-weight: 700; }

/* === Waiting === */
.os-waiting {
    margin: 4px 24px 16px;
    padding: 28px 20px;
    text-align: center;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 14px;
}

.os-waiting-icon { font-size: 2.2rem; margin-bottom: 12px; }

.os-waiting h4 {
    color: #1e40af;
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.os-waiting p {
    color: #6b7280;
    font-size: 0.85rem;
    line-height: 1.6;
    margin-bottom: 16px;
}

.os-reload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(59,130,246,0.25);
}

.os-reload-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(59,130,246,0.35);
}

/* === Pending === */
.os-pending {
    margin: 4px 24px 16px;
    padding: 28px 20px;
    text-align: center;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 14px;
}

.os-pending h4 {
    color: #92400e;
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.os-pending p { color: #6b7280; font-size: 0.85rem; }

.os-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #fde68a;
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: os-spin 0.8s linear infinite;
    margin: 16px auto 0;
}

@keyframes os-spin { to { transform: rotate(360deg); } }

/* === Actions === */
.os-actions {
    padding: 16px 24px 24px;
    display: flex;
    gap: 10px;
}

.os-btn-home {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 13px 18px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    color: #4b5563;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.os-btn-home:hover { background: #e5e7eb; color: #1f2937; text-decoration: none; }

.os-btn-contact {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 13px 18px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 4px 12px rgba(59,130,246,0.25);
}

.os-btn-contact:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(59,130,246,0.35);
    color: #fff;
    text-decoration: none;
}

.os-not-found {
    text-align: center;
    padding: 60px 20px;
}

.os-not-found i { font-size: 3rem; color: #d1d5db; margin-bottom: 16px; }
.os-not-found h3 { color: #6b7280; font-size: 1.1rem; margin-bottom: 20px; }

@media (max-width: 480px) {
    .os-page { padding: 20px 12px; }
    .os-info { grid-template-columns: 1fr; gap: 8px; }
    .os-step-line { width: 32px; }
    .os-card-header, .os-info, .os-actions { padding-left: 18px; padding-right: 18px; }
    .os-credentials, .os-countdown, .os-waiting, .os-pending { margin-left: 18px; margin-right: 18px; }
    .os-actions { flex-direction: column; }
}
</style>
@endsection

@section('content')
<div class="os-page">
<div class="os-container">

    @if($order)
    @php
        $isPending = $order->status === 'pending';
        $isPaid = in_array($order->status, ['paid', 'completed']) && !$order->account;
        $isCompleted = in_array($order->status, ['paid', 'completed']) && $order->account;
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
            <div class="os-card-header">
                <div class="os-order-code">
                    Đơn hàng <strong>{{ $order->tracking_code }}</strong>
                </div>
                <span class="os-badge {{ $order->status === 'completed' ? 'completed' : ($order->status === 'paid' ? 'paid' : 'pending') }}">
                    {{ $order->status === 'completed' ? '✓ Hoàn thành' : ($order->status === 'paid' ? '✓ Đã thanh toán' : '⏳ Chờ thanh toán') }}
                </span>
            </div>

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

            @if(in_array($order->status, ['paid', 'completed']) && $order->account)
                <div class="os-credentials">
                    <div class="os-cred-header">
                        <i class="fas fa-key"></i>
                        <span>Thông tin tài khoản</span>
                    </div>
                    <div class="os-cred-row">
                        <span class="os-cred-label">Username</span>
                        <div class="os-cred-value">
                            <span>{{ $order->account->username }}</span>
                            <button class="os-copy-btn" onclick="copyText('{{ $order->account->username }}', this)"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <div class="os-cred-row">
                        <span class="os-cred-label">Password</span>
                        <div class="os-cred-value">
                            <span>{{ $order->assigned_password ?? $order->account->password }}</span>
                            <button class="os-copy-btn" onclick="copyText('{{ $order->assigned_password ?? $order->account->password }}', this)"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                </div>

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

            @elseif(in_array($order->status, ['paid', 'completed']) && !$order->account)
                <div class="os-waiting">
                    <div class="os-waiting-icon">✅</div>
                    <h4>Đã thanh toán thành công!</h4>
                    <p>Hệ thống đang cấp tài khoản. Trang sẽ tự động cập nhật.<br>Nếu quá 30 giây, liên hệ Zalo: <strong style="color:#2563eb;">0777333763</strong></p>
                    <button class="os-reload-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Tải lại trang</button>
                </div>

            @elseif($order->status === 'pending')
                <div class="os-pending">
                    <h4>⏳ Đang chờ thanh toán</h4>
                    <p>Hệ thống sẽ tự động cập nhật khi nhận được thanh toán.</p>
                    <div class="os-spinner"></div>
                </div>
            @endif

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
function updateCountdown() {
    var el = document.getElementById('countdown');
    if (!el) return;
    var expireMs = parseInt(el.getAttribute('data-expire')) * 1000;
    var diff = expireMs - Date.now();
    if (diff <= 0) {
        el.textContent = 'Đã hết hạn';
        el.className = 'os-countdown-expired';
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
