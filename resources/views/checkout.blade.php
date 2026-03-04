@extends('layouts.app')

@section('title', 'Xác nhận đơn hàng - UnlockTool.us')

@section('head')
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<style>
.grecaptcha-badge { visibility: hidden !important; }

.co-page {
    min-height: 90vh;
    background: linear-gradient(145deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    padding: 40px 16px;
    position: relative;
    overflow: hidden;
}

.co-page::before {
    content: '';
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
    top: -150px;
    right: -150px;
    border-radius: 50%;
}

.co-container {
    max-width: 720px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.co-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #93c5fd;
    text-decoration: none;
    margin-bottom: 24px;
    transition: color 0.2s;
}

.co-back:hover { color: #bfdbfe; text-decoration: none; }

.co-title {
    font-size: 1.6rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 24px;
}

.co-grid {
    display: grid;
    grid-template-columns: 1fr 260px;
    gap: 20px;
    align-items: start;
}

/* Main Card */
.co-card {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}

.co-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 22px 24px;
    background: linear-gradient(135deg, rgba(59,130,246,0.12), rgba(37,99,235,0.06));
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.co-card-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
    flex-shrink: 0;
}

.co-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.co-card-desc {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.4);
    margin-top: 2px;
}

.co-alert {
    margin: 20px 20px 0;
    padding: 14px 18px;
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.25);
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #fcd34d;
    display: flex;
    align-items: center;
    gap: 10px;
}

.co-alert i { color: #f59e0b; flex-shrink: 0; }

.co-details { padding: 20px 24px; }

.co-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.co-detail-row:last-child { border-bottom: none; }

.co-detail-label {
    font-size: 0.92rem;
    font-weight: 500;
    color: rgba(255,255,255,0.5);
}

.co-detail-value {
    font-size: 1rem;
    font-weight: 700;
    color: #93c5fd;
}

.co-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(5,150,105,0.04));
    border-top: 1px solid rgba(16,185,129,0.2);
}

.co-total-label {
    font-size: 1.05rem;
    font-weight: 700;
    color: rgba(255,255,255,0.7);
}

.co-total-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: #6ee7b7;
}

.co-actions { padding: 0 24px 24px; }

.co-btn-pay {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 16px 20px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 20px rgba(16,185,129,0.35);
}

.co-btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 28px rgba(16,185,129,0.5);
}

.co-btn-pay:disabled {
    background: rgba(255,255,255,0.1);
    box-shadow: none;
    transform: none;
    cursor: not-allowed;
    color: rgba(255,255,255,0.4);
}

.co-back-link {
    display: block;
    text-align: center;
    margin-top: 14px;
    font-size: 0.85rem;
    color: rgba(255,255,255,0.4);
    text-decoration: none;
    transition: color 0.2s;
}

.co-back-link:hover { color: rgba(255,255,255,0.7); }

.co-error {
    margin: 0 24px 16px;
    padding: 12px 16px;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 10px;
    font-size: 0.88rem;
    color: #fca5a5;
}

/* Sidebar */
.co-sidebar {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.co-sidebar-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 18px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    transition: border-color 0.2s;
}

.co-sidebar-card:hover {
    border-color: rgba(255,255,255,0.15);
}

.co-sidebar-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.co-sidebar-icon.security {
    background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(37,99,235,0.12));
    color: #93c5fd;
}

.co-sidebar-icon.auto {
    background: linear-gradient(135deg, rgba(16,185,129,0.2), rgba(5,150,105,0.12));
    color: #6ee7b7;
}

.co-sidebar-icon.support {
    background: linear-gradient(135deg, rgba(244,63,94,0.2), rgba(225,29,72,0.12));
    color: #fda4af;
}

.co-sidebar-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: rgba(255,255,255,0.8);
    margin-bottom: 3px;
}

.co-sidebar-desc {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.35);
    line-height: 1.4;
}

@media (max-width: 768px) {
    .co-grid { grid-template-columns: 1fr; }
    .co-sidebar {
        flex-direction: row;
        overflow-x: auto;
        gap: 10px;
        padding-bottom: 4px;
    }
    .co-sidebar-card {
        flex-shrink: 0;
        min-width: 200px;
    }
    .co-title { font-size: 1.3rem; }
    .co-total-value { font-size: 1.3rem; }
}
</style>
@endsection

@section('content')
<div class="co-page">
<div class="co-container">
    <a href="{{ route('home') }}" class="co-back"><i class="fas fa-arrow-left"></i> Quay lại</a>

    <h1 class="co-title">Xác Nhận Đơn Hàng</h1>

    <div class="co-grid">
        {{-- Main Card --}}
        <div class="co-card">
            <div class="co-card-header">
                <div class="co-card-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <h2 class="co-card-title">Xác nhận tạo đơn hàng</h2>
                    <p class="co-card-desc">Mã đơn sẽ được tạo sau khi xác nhận</p>
                </div>
            </div>

            <div class="co-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span>Kiểm tra thông tin và nhấn nút bên dưới để tiếp tục</span>
            </div>

            <div class="co-details">
                <div class="co-detail-row">
                    <span class="co-detail-label">Dịch vụ</span>
                    <span class="co-detail-value">{{ $packageName }}</span>
                </div>
                <div class="co-detail-row">
                    <span class="co-detail-label">Giá tiền</span>
                    <span class="co-detail-value">{{ $formattedPrice }}</span>
                </div>
            </div>

            <div class="co-total">
                <span class="co-total-label">Tổng thanh toán</span>
                <span class="co-total-value">{{ $formattedPrice }}</span>
            </div>

            @if(session('error'))
                <div class="co-error">{{ session('error') }}</div>
            @endif

            <div class="co-actions">
                <form method="post" action="{{ route('checkout.create') }}" id="checkoutForm">
                    @csrf
                    <input type="hidden" name="price_id" value="{{ $price->id }}">
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                    <button type="submit" class="co-btn-pay" id="submitBtn">
                        <i class="fas fa-check-circle"></i> Xác nhận và Tạo đơn hàng
                    </button>
                </form>
                <a href="{{ route('home') }}" class="co-back-link">← Quay về trang chủ</a>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="co-sidebar">
            <div class="co-sidebar-card">
                <div class="co-sidebar-icon security"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <div class="co-sidebar-title">Bảo mật 100%</div>
                    <div class="co-sidebar-desc">Thông tin được mã hóa và bảo vệ bằng SSL</div>
                </div>
            </div>
            <div class="co-sidebar-card">
                <div class="co-sidebar-icon auto"><i class="fas fa-bolt"></i></div>
                <div>
                    <div class="co-sidebar-title">Kích hoạt tự động</div>
                    <div class="co-sidebar-desc">Tài khoản được kích hoạt ngay sau thanh toán</div>
                </div>
            </div>
            <div class="co-sidebar-card">
                <div class="co-sidebar-icon support"><i class="fas fa-headset"></i></div>
                <div>
                    <div class="co-sidebar-title">Hỗ trợ 24/7</div>
                    <div class="co-sidebar-desc">Liên hệ qua Zalo bất cứ lúc nào</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ config("services.recaptcha.site_key") }}', {action: 'create_order'}).then(function(token) {
            document.getElementById('recaptcha_token').value = token;
            document.getElementById('checkoutForm').submit();
        });
    });
});
</script>
@endsection
