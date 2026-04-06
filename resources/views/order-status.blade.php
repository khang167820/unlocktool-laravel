@extends('layouts.app')

@section('title', 'Trạng thái đơn hàng - UnlockTool.us')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
/* ===== PREMIUM ORDER STATUS PAGE ===== */
.os-page {
    min-height: 90vh;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    padding: 40px 16px;
    position: relative;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
}

/* Animated background orbs */
.os-page::before {
    content: '';
    position: absolute;
    top: -120px;
    right: -120px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%);
    border-radius: 50%;
    animation: os-float 8s ease-in-out infinite;
}
.os-page::after {
    content: '';
    position: absolute;
    bottom: -80px;
    left: -80px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(16,185,129,0.12) 0%, transparent 70%);
    border-radius: 50%;
    animation: os-float 10s ease-in-out infinite reverse;
}
@keyframes os-float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-30px) scale(1.05); }
}

.os-container {
    max-width: 520px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* ===== PROGRESS STEPS ===== */
.os-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 32px;
    padding: 0 10px;
}

.os-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 1;
}

.os-step-dot {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 700;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.os-step-dot.done {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    box-shadow: 0 0 20px rgba(16,185,129,0.4), 0 0 60px rgba(16,185,129,0.1);
}

.os-step-dot.active {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff;
    box-shadow: 0 0 20px rgba(59,130,246,0.4), 0 0 60px rgba(99,102,241,0.1);
    animation: os-glow 2s ease-in-out infinite;
}

.os-step-dot.waiting {
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.3);
    border: 2px solid rgba(255,255,255,0.1);
}

@keyframes os-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(59,130,246,0.4), 0 0 60px rgba(99,102,241,0.1); }
    50% { box-shadow: 0 0 30px rgba(59,130,246,0.6), 0 0 80px rgba(99,102,241,0.2); }
}

.os-step-label {
    font-size: 0.7rem;
    font-weight: 700;
    margin-top: 10px;
    text-align: center;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.os-step-label.done { color: #34d399; }
.os-step-label.active { color: #93c5fd; }
.os-step-label.waiting { color: rgba(255,255,255,0.25); }

.os-step-line {
    width: 52px;
    height: 3px;
    border-radius: 4px;
    margin: 0 8px;
    margin-bottom: 28px;
    transition: all 0.4s;
}
.os-step-line.done {
    background: linear-gradient(90deg, #10b981, #34d399);
    box-shadow: 0 0 8px rgba(16,185,129,0.3);
}
.os-step-line.waiting {
    background: rgba(255,255,255,0.08);
}

/* ===== GLASS CARD ===== */
.os-card {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
    overflow: hidden;
}

.os-card-header {
    padding: 22px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.os-order-code {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.5);
    font-weight: 500;
}
.os-order-code strong {
    color: #fff;
    font-size: 1.05rem;
    font-weight: 800;
}

/* Status Badges */
.os-badge {
    padding: 6px 16px;
    border-radius: 24px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.os-badge.completed {
    background: rgba(16,185,129,0.15);
    color: #34d399;
    border: 1px solid rgba(16,185,129,0.3);
}
.os-badge.paid {
    background: rgba(59,130,246,0.15);
    color: #93c5fd;
    border: 1px solid rgba(59,130,246,0.3);
}
.os-badge.pending {
    background: rgba(245,158,11,0.15);
    color: #fbbf24;
    border: 1px solid rgba(245,158,11,0.3);
}

/* ===== ORDER INFO ===== */
.os-info {
    padding: 16px 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.os-info-item {
    padding: 16px 18px;
    background: rgba(255,255,255,0.04);
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.06);
    transition: all 0.3s;
}
.os-info-item:hover {
    background: rgba(255,255,255,0.07);
    border-color: rgba(255,255,255,0.12);
}

.os-info-label {
    font-size: 0.7rem;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    margin-bottom: 6px;
}
.os-info-value {
    font-size: 1.2rem;
    color: #fff;
    font-weight: 800;
}

/* ===== RENTAL PERIOD ===== */
.os-rental-period {
    margin: 6px 20px 16px;
    background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.05));
    border: 1px solid rgba(99,102,241,0.25);
    border-radius: 16px;
    overflow: hidden;
    position: relative;
}
.os-rental-period::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(139,92,246,0.5), transparent);
}
.os-rental-row {
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(99,102,241,0.1);
}
.os-rental-row:last-child { border-bottom: none; }
.os-rental-label {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.5);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.os-rental-label .dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.os-rental-label .dot.start { background: #34d399; box-shadow: 0 0 8px rgba(52,211,153,0.4); }
.os-rental-label .dot.end { background: #f87171; box-shadow: 0 0 8px rgba(248,113,113,0.4); }
.os-rental-value {
    font-size: 0.95rem;
    color: #fff;
    font-weight: 700;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    letter-spacing: 0.5px;
}

/* ===== CREDENTIALS (PREMIUM) ===== */
.os-credentials {
    margin: 6px 20px 16px;
    background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(52,211,153,0.05));
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: 16px;
    overflow: hidden;
    position: relative;
}
.os-credentials::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(52,211,153,0.5), transparent);
}

.os-cred-header {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(16,185,129,0.15);
    display: flex;
    align-items: center;
    gap: 10px;
}
.os-cred-header i {
    color: #34d399;
    font-size: 1.1rem;
    filter: drop-shadow(0 0 6px rgba(52,211,153,0.4));
}
.os-cred-header span {
    font-size: 0.9rem;
    font-weight: 800;
    color: #6ee7b7;
    letter-spacing: 0.3px;
}

.os-cred-row {
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(16,185,129,0.08);
    transition: background 0.2s;
}
.os-cred-row:last-child { border-bottom: none; }
.os-cred-row:hover { background: rgba(16,185,129,0.05); }

.os-cred-label {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.5);
    font-weight: 600;
}

.os-cred-value {
    font-size: 1.05rem;
    color: #fff;
    font-weight: 700;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    display: flex;
    align-items: center;
    gap: 10px;
}

.os-copy-btn {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid rgba(52,211,153,0.3);
    background: rgba(52,211,153,0.1);
    color: #34d399;
    font-size: 0.8rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.os-copy-btn:hover {
    background: rgba(52,211,153,0.2);
    transform: scale(1.08);
    box-shadow: 0 0 12px rgba(52,211,153,0.2);
}
.os-copy-btn.copied {
    background: #059669;
    color: #fff;
    border-color: #059669;
    box-shadow: 0 0 16px rgba(5,150,105,0.4);
    animation: os-copied-pop 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes os-copied-pop {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* ===== COUNTDOWN (PREMIUM) ===== */
.os-countdown {
    margin: 0 20px 16px;
    padding: 16px 20px;
    background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(217,119,6,0.05));
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}
.os-countdown::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(245,158,11,0.4), transparent);
}

.os-countdown-label {
    font-size: 0.8rem;
    color: #fbbf24;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.os-countdown-label i {
    color: #f59e0b;
    filter: drop-shadow(0 0 4px rgba(245,158,11,0.4));
}

.os-countdown-value {
    font-size: 1.4rem;
    font-weight: 900;
    color: #fbbf24;
    font-family: 'JetBrains Mono', monospace;
    letter-spacing: 2px;
    text-shadow: 0 0 10px rgba(251,191,36,0.3);
}

.os-countdown-expired {
    font-size: 0.9rem;
    color: #f87171;
    font-weight: 700;
}

/* ===== WAITING STATE ===== */
.os-waiting {
    margin: 6px 20px 16px;
    padding: 32px 24px;
    text-align: center;
    background: linear-gradient(135deg, rgba(59,130,246,0.08), rgba(99,102,241,0.05));
    border: 1px solid rgba(59,130,246,0.2);
    border-radius: 16px;
    position: relative;
}
.os-waiting::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(59,130,246,0.4), transparent);
}

.os-waiting-icon {
    font-size: 2.5rem;
    margin-bottom: 14px;
    filter: drop-shadow(0 0 8px rgba(59,130,246,0.3));
}

.os-waiting h4 {
    color: #93c5fd;
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: 10px;
}
.os-waiting p {
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
    line-height: 1.7;
    margin-bottom: 18px;
}
.os-waiting p strong { color: #93c5fd; }

.os-reload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba(59,130,246,0.3);
    font-family: 'Inter', sans-serif;
}
.os-reload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(59,130,246,0.4);
}

/* ===== PENDING STATE ===== */
.os-pending {
    margin: 6px 20px 16px;
    padding: 32px 24px;
    text-align: center;
    background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(217,119,6,0.04));
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 16px;
    position: relative;
}
.os-pending::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(245,158,11,0.4), transparent);
}

.os-pending h4 {
    color: #fbbf24;
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: 10px;
}
.os-pending p {
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
}

.os-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(245,158,11,0.15);
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: os-spin 0.8s linear infinite;
    margin: 20px auto 0;
    box-shadow: 0 0 12px rgba(245,158,11,0.15);
}
@keyframes os-spin { to { transform: rotate(360deg); } }

/* ===== ACTIONS ===== */
.os-actions {
    padding: 18px 20px 24px;
    display: flex;
    gap: 10px;
}

.os-btn-home {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 18px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    color: rgba(255,255,255,0.7);
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    font-family: 'Inter', sans-serif;
}
.os-btn-home:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
    text-decoration: none;
    border-color: rgba(255,255,255,0.2);
}

.os-btn-contact {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 22px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba(59,130,246,0.3);
    font-family: 'Inter', sans-serif;
}
.os-btn-contact:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59,130,246,0.4);
    color: #fff;
    text-decoration: none;
}

/* ===== NOT FOUND ===== */
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
    color: rgba(255,255,255,0.5);
    font-size: 1.1rem;
    margin-bottom: 24px;
}

/* ===== BRANDING FOOTER ===== */
.os-branding {
    text-align: center;
    margin-top: 24px;
    font-size: 0.72rem;
    color: rgba(255,255,255,0.2);
    font-weight: 500;
    letter-spacing: 1px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 480px) {
    .os-page { padding: 24px 12px; }
    .os-info { grid-template-columns: 1fr; gap: 8px; }
    .os-step-line { width: 28px; }
    .os-step-dot { width: 42px; height: 42px; font-size: 0.85rem; }
    .os-card-header, .os-info, .os-actions { padding-left: 16px; padding-right: 16px; }
    .os-credentials, .os-countdown, .os-waiting, .os-pending, .os-rental-period { margin-left: 16px; margin-right: 16px; }
    .os-actions { flex-direction: column; }
    .os-countdown-value { font-size: 1.15rem; }
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

            @if($rentalStart && $rentalEnd)
            <div class="os-rental-period">
                <div class="os-rental-row">
                    <span class="os-rental-label"><span class="dot start"></span> Bắt đầu thuê</span>
                    <span class="os-rental-value">{{ $rentalStart }}</span>
                </div>
                <div class="os-rental-row">
                    <span class="os-rental-label"><span class="dot end"></span> Hết hạn</span>
                    <span class="os-rental-value">{{ $rentalEnd }}</span>
                </div>
            </div>
            @endif

            @if(in_array($order->status, ['paid', 'completed']) && $order->account)
                @php
                    $canShowCredentials = !$order->account->is_available && !$order->account->password_changed && (!$timeRemaining || !$timeRemaining['expired']);
                @endphp

                @if($canShowCredentials)
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
                            <span>{{ $order->account->password }}</span>
                            <button class="os-copy-btn" onclick="copyText('{{ $order->account->password }}', this)"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                </div>
                @else
                    @if($timeRemaining && !$timeRemaining['expired'])
                    {{-- Còn thời gian nhưng admin đã đổi pass hoặc thu hồi --}}
                    <div class="os-credentials" style="border-color: rgba(245,158,11,0.25); background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.05));">
                        <div class="os-cred-header" style="border-bottom-color: rgba(245,158,11,0.15);">
                            <i class="fas fa-exclamation-triangle" style="color: #fbbf24;"></i>
                            <span style="color: #fde68a;">Mật khẩu đã được thay đổi</span>
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
                                <span style="color: #fbbf24;">••••••••</span>
                            </div>
                        </div>
                        <div style="padding: 12px 20px;">
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0 0 14px; line-height: 1.6;">
                                Mật khẩu tài khoản đã được admin thay đổi. Bạn vẫn còn thời gian thuê — vui lòng liên hệ admin để được cấp lại mật khẩu mới.
                            </p>
                            <a href="https://zalo.me/0777333763" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border-radius: 10px; font-size: 0.85rem; font-weight: 700; text-decoration: none; transition: all 0.3s; box-shadow: 0 4px 12px rgba(245,158,11,0.3);">
                                <i class="fas fa-comment-dots"></i> Liên hệ Admin qua Zalo
                            </a>
                        </div>
                    </div>
                    @else
                    {{-- Hết hạn --}}
                    <div class="os-credentials" style="border-color: rgba(239,68,68,0.25); background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(239,68,68,0.05));">
                        <div class="os-cred-header" style="border-bottom-color: rgba(239,68,68,0.15);">
                            <i class="fas fa-lock" style="color: #f87171;"></i>
                            <span style="color: #fca5a5;">Phiên thuê đã kết thúc</span>
                        </div>
                        <div class="os-cred-row">
                            <span class="os-cred-label">Username</span>
                            <div class="os-cred-value">
                                <span>{{ $order->account->username }}</span>
                            </div>
                        </div>
                        <div class="os-cred-row">
                            <span class="os-cred-label">Password</span>
                            <div class="os-cred-value">
                                <span style="color: #f87171;">••••••••</span>
                            </div>
                        </div>
                    </div>
                    @endif
                @endif

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
                    <p>Hệ thống đang cấp tài khoản. Trang sẽ tự động cập nhật.<br>Nếu quá 30 giây, liên hệ Zalo: <strong>0777333763</strong></p>
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

    <div class="os-branding">UNLOCKTOOL.US — THUÊ TỰ ĐỘNG 24/7</div>

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
