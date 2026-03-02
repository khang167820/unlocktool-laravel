@extends('layouts.app')

@section('title', 'Xác nhận đơn hàng - UnlockTool.us')

@section('head')
<script src="https://www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}"></script>
<style>
.grecaptcha-badge { visibility: hidden !important; }
.checkout-page {
    min-height: 85vh;
    background: #e8edf2;
    padding: 32px 16px;
}

.checkout-breadcrumb {
    max-width: 800px;
    margin: 0 auto 20px;
}

.checkout-breadcrumb a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #0068ff;
    text-decoration: none;
    transition: color 0.2s;
}

.checkout-breadcrumb a:hover {
    color: #0051cc;
}

.checkout-page-title {
    max-width: 800px;
    margin: 0 auto 24px;
    font-size: 1.6rem;
    font-weight: 800;
    color: #1a1a2e;
}

.checkout-grid {
    max-width: 800px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 20px;
    align-items: start;
}

/* === Main Card === */
.checkout-main-card {
    background: #fff;
    border-radius: 16px;
    border: 2px solid #d1d5db;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.checkout-main-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 22px 24px;
    background: linear-gradient(135deg, #1e3a5f, #0e2442);
    border-bottom: none;
}

.checkout-main-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: rgba(255,255,255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #93c5fd;
    flex-shrink: 0;
}

.checkout-main-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.checkout-main-desc {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.6);
    margin-top: 2px;
}

.checkout-alert {
    margin: 20px 20px 0;
    padding: 14px 18px;
    background: #fef3c7;
    border: 2px solid #f59e0b;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #78350f;
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkout-alert i {
    flex-shrink: 0;
    color: #d97706;
    font-size: 1rem;
}

.checkout-details {
    padding: 20px 24px;
}

.checkout-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f3f4f6;
}

.checkout-detail-row:last-child {
    border-bottom: none;
}

.checkout-detail-label {
    font-size: 0.92rem;
    font-weight: 500;
    color: #4b5563;
}

.checkout-detail-value {
    font-size: 1rem;
    font-weight: 800;
    color: #1e40af;
}

.checkout-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border-top: 2px solid #a7f3d0;
}

.checkout-total-label {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1f2937;
}

.checkout-total-value {
    font-size: 1.7rem;
    font-weight: 800;
    color: #dc2626;
}

.checkout-total-value small {
    font-size: 0.85rem;
    font-weight: 600;
}

.checkout-actions {
    padding: 0 24px 24px;
}

.checkout-btn-pay {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 15px 20px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 16px rgba(22, 163, 74, 0.35);
}

.checkout-btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(22, 163, 74, 0.5);
}

.checkout-btn-pay:disabled {
    background: #9ca3af;
    box-shadow: none;
    transform: none;
    cursor: not-allowed;
}

.checkout-back-link {
    display: block;
    text-align: center;
    margin-top: 14px;
    font-size: 0.88rem;
    color: #6b7280;
    text-decoration: none;
    transition: color 0.2s;
}

.checkout-back-link:hover {
    color: #374151;
}

/* === Sidebar Cards === */
.checkout-sidebar {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.sidebar-card {
    background: #fff;
    border: 2px solid #d1d5db;
    border-radius: 14px;
    padding: 18px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.sidebar-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.sidebar-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.sidebar-icon.security {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
}

.sidebar-icon.auto {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
}

.sidebar-icon.support {
    background: linear-gradient(135deg, #f43f5e, #e11d48);
    color: #fff;
}

.sidebar-title {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 3px;
}

.sidebar-desc {
    font-size: 0.78rem;
    color: #9ca3af;
    line-height: 1.4;
}

/* === Mobile === */
@media (max-width: 768px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }

    .checkout-sidebar {
        flex-direction: row;
        overflow-x: auto;
        gap: 10px;
        padding-bottom: 4px;
    }

    .sidebar-card {
        flex-shrink: 0;
        min-width: 200px;
    }

    .checkout-page-title {
        font-size: 1.3rem;
    }

    .checkout-total-value {
        font-size: 1.3rem;
    }
}
</style>
@endsection

@section('content')
<div class="checkout-page">
    <div class="checkout-breadcrumb">
        <a href="{{ route('home') }}"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>

    <h1 class="checkout-page-title">Xác Nhận Đơn Hàng</h1>

    <div class="checkout-grid">
        {{-- Main Card --}}
        <div class="checkout-main-card">
            <div class="checkout-main-header">
                <div class="checkout-main-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div>
                    <h2 class="checkout-main-title">Xác nhận tạo đơn hàng</h2>
                    <p class="checkout-main-desc">Mã đơn sẽ được tạo sau khi xác nhận</p>
                </div>
            </div>

            <div class="checkout-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span>Kiểm tra thông tin và nhấn nút bên dưới để tiếp tục</span>
            </div>

            <div class="checkout-details">
                <div class="checkout-detail-row">
                    <span class="checkout-detail-label">Dịch vụ</span>
                    <span class="checkout-detail-value">{{ $packageName }}</span>
                </div>
                <div class="checkout-detail-row">
                    <span class="checkout-detail-label">Giá tiền</span>
                    <span class="checkout-detail-value">{{ $formattedPrice }}</span>
                </div>
            </div>

            <div class="checkout-total">
                <span class="checkout-total-label">Tổng thanh toán</span>
                <span class="checkout-total-value">{{ $formattedPrice }}</span>
            </div>

            @if(session('error'))
                <div class="alert alert-danger" style="margin:0 24px 16px; border-radius:10px; font-size:0.88rem;">{{ session('error') }}</div>
            @endif

            <div class="checkout-actions">
                <form method="post" action="{{ route('checkout.create') }}" id="checkoutForm">
                    @csrf
                    <input type="hidden" name="price_id" value="{{ $price->id }}">
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                    <button type="submit" class="checkout-btn-pay" id="submitBtn">
                        <i class="fas fa-check-circle"></i> Xác nhận và Tạo đơn hàng
                    </button>
                </form>
                <a href="{{ route('home') }}" class="checkout-back-link">← Quay về trang chủ</a>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="checkout-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-icon security">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <div class="sidebar-title">Bảo mật 100%</div>
                    <div class="sidebar-desc">Thông tin được mã hóa và bảo vệ bằng SSL</div>
                </div>
            </div>
            <div class="sidebar-card">
                <div class="sidebar-icon auto">
                    <i class="fas fa-bolt"></i>
                </div>
                <div>
                    <div class="sidebar-title">Kích hoạt tự động</div>
                    <div class="sidebar-desc">Tài khoản được kích hoạt ngay sau thanh toán</div>
                </div>
            </div>
            <div class="sidebar-card">
                <div class="sidebar-icon support">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <div class="sidebar-title">Hỗ trợ 24/7</div>
                    <div class="sidebar-desc">Liên hệ qua Zalo bất cứ lúc nào</div>
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
        grecaptcha.execute('{{ env("RECAPTCHA_SITE_KEY") }}', {action: 'create_order'}).then(function(token) {
            document.getElementById('recaptcha_token').value = token;
            document.getElementById('checkoutForm').submit();
        });
    });
});
</script>
@endsection
