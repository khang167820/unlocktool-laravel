@extends('layouts.app')

@section('title', 'Xác nhận đơn hàng - UnlockTool.us')

@section('head')
<script src="https://www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}"></script>
<style>
.checkout-wrapper {
    min-height: 85vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
}

.checkout-card {
    max-width: 480px;
    width: 100%;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.checkout-header {
    background: linear-gradient(135deg, #0e0e1a, #1a1a2e);
    padding: 28px 28px 24px;
    text-align: center;
    position: relative;
}

.checkout-header::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 20px;
    background: #fff;
    border-radius: 20px 20px 0 0;
}

.checkout-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #0068ff, #0051cc);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    font-size: 1.4rem;
    color: #fff;
}

.checkout-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #fff;
    margin: 0;
}

.checkout-subtitle {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.6);
    margin-top: 6px;
}

.checkout-body {
    padding: 24px 28px 28px;
}

.checkout-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f3f4f6;
}

.checkout-info-row:last-of-type {
    border-bottom: none;
}

.checkout-info-label {
    font-size: 0.88rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkout-info-label i {
    font-size: 0.85rem;
    color: #9ca3af;
    width: 18px;
    text-align: center;
}

.checkout-info-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1f2937;
}

.checkout-price-value {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0068ff;
}

.checkout-note {
    margin: 20px 0;
    padding: 14px 16px;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border: 1px solid #a7f3d0;
    border-radius: 12px;
    font-size: 0.82rem;
    color: #065f46;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.5;
}

.checkout-note i {
    margin-top: 2px;
    flex-shrink: 0;
}

.checkout-btn-confirm {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px 20px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #0068ff, #0051cc);
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 16px rgba(0, 104, 255, 0.35);
}

.checkout-btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(0, 104, 255, 0.5);
}

.checkout-btn-confirm:disabled {
    background: #9ca3af;
    box-shadow: none;
    transform: none;
    cursor: not-allowed;
}

.checkout-btn-back {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 12px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: transparent;
    color: #6b7280;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none !important;
    margin-top: 10px;
}

.checkout-btn-back:hover {
    border-color: #d1d5db;
    background: #f9fafb;
    color: #374151;
    text-decoration: none;
}

.checkout-security {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.73rem;
    color: #9ca3af;
    margin-top: 16px;
}

.checkout-security i {
    color: #10b981;
}
</style>
@endsection

@section('content')
<div class="checkout-wrapper">
    <div class="checkout-card">
        <div class="checkout-header">
            <div class="checkout-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2 class="checkout-title">Xác nhận đơn thuê</h2>
            <p class="checkout-subtitle">Kiểm tra thông tin trước khi thanh toán</p>
        </div>
        <div class="checkout-body">
            <div class="checkout-info-row">
                <span class="checkout-info-label"><i class="fas fa-cube"></i> Gói thuê</span>
                <span class="checkout-info-value">{{ $packageName }}</span>
            </div>
            <div class="checkout-info-row">
                <span class="checkout-info-label"><i class="fas fa-tag"></i> Giá tiền</span>
                <span class="checkout-price-value">{{ $formattedPrice }}</span>
            </div>

            <div class="checkout-note">
                <i class="fas fa-shield-alt"></i>
                <span>Tài khoản sẽ được cấp tự động ngay sau khi thanh toán thành công. Hệ thống hoạt động 24/7.</span>
            </div>

            @if(session('error'))
                <div class="alert alert-danger" style="border-radius:12px; font-size:0.88rem;">{{ session('error') }}</div>
            @endif

            <form method="post" action="{{ route('checkout.create') }}" id="checkoutForm">
                @csrf
                <input type="hidden" name="price_id" value="{{ $price->id }}">
                <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                <button type="submit" class="checkout-btn-confirm" id="submitBtn">
                    <i class="fas fa-check-circle"></i> Xác nhận và thanh toán
                </button>
                <a href="{{ route('home') }}" class="checkout-btn-back">
                    <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                </a>
            </form>

            <div class="checkout-security">
                <i class="fas fa-lock"></i> Giao dịch được bảo mật bởi hệ thống tự động
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
