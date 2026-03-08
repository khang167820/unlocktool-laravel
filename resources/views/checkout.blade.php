@extends('layouts.app')

@section('title', 'Xác nhận đơn hàng - UnlockTool.us')

@section('head')
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<style>
.grecaptcha-badge { visibility: hidden !important; }

.co-page {
    min-height: 85vh;
    background: #f0f2f5;
    padding: 32px 16px;
}

.co-container {
    max-width: 720px;
    margin: 0 auto;
}

.co-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
    margin-bottom: 20px;
    transition: color 0.2s;
}

.co-back:hover { color: #1d4ed8; text-decoration: none; }

.co-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 20px;
}

.co-grid {
    display: grid;
    grid-template-columns: 1fr 260px;
    gap: 16px;
    align-items: start;
}

.co-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    overflow: hidden;
}

.co-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 22px;
    background: linear-gradient(135deg, #1e3a5f, #0e2442);
    border-bottom: none;
}

.co-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #93c5fd;
    flex-shrink: 0;
}

.co-card-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.co-card-desc {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.5);
    margin-top: 2px;
}

.co-alert {
    margin: 18px 20px 0;
    padding: 12px 16px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #92400e;
    display: flex;
    align-items: center;
    gap: 10px;
}

.co-alert i { color: #d97706; flex-shrink: 0; }

.co-details { padding: 18px 22px; }

.co-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 13px 0;
    border-bottom: 1px solid #f3f4f6;
}

.co-detail-row:last-child { border-bottom: none; }

.co-detail-label { font-size: 0.92rem; font-weight: 500; color: #6b7280; }
.co-detail-value { font-size: 1rem; font-weight: 800; color: #1e40af; }

.co-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 22px;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border-top: 1px solid #a7f3d0;
}

.co-total-label { font-size: 1.02rem; font-weight: 700; color: #1f2937; }

.co-total-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #dc2626;
}

.co-error {
    margin: 0 22px 14px;
    padding: 12px 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    font-size: 0.88rem;
    color: #b91c1c;
}

.co-actions { padding: 0 22px 22px; }

.co-btn-pay {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px 20px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #fff;
    font-size: 1.02rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 16px rgba(22,163,74,0.3);
}

.co-btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(22,163,74,0.45);
}

.co-btn-pay:disabled {
    background: #9ca3af;
    box-shadow: none;
    transform: none;
    cursor: not-allowed;
}

.co-back-link {
    display: block;
    text-align: center;
    margin-top: 12px;
    font-size: 0.85rem;
    color: #9ca3af;
    text-decoration: none;
}

.co-back-link:hover { color: #6b7280; }

/* Sidebar */
.co-sidebar { display: flex; flex-direction: column; gap: 10px; }

.co-sidebar-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}

.co-sidebar-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.co-sidebar-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.co-sidebar-icon.security { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
.co-sidebar-icon.auto { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
.co-sidebar-icon.support { background: linear-gradient(135deg, #f43f5e, #e11d48); color: #fff; }

.co-sidebar-title { font-size: 0.86rem; font-weight: 700; color: #1f2937; margin-bottom: 2px; }
.co-sidebar-desc { font-size: 0.76rem; color: #9ca3af; line-height: 1.4; }

@media (max-width: 768px) {
    .co-grid { grid-template-columns: 1fr; }
    .co-sidebar {
        flex-direction: row;
        overflow-x: auto;
        gap: 10px;
    }
    .co-sidebar-card { flex-shrink: 0; min-width: 190px; }
    .co-title { font-size: 1.3rem; }
    .co-total-value { font-size: 1.25rem; }
}
</style>
@endsection

@section('content')
<div class="co-page">
<div class="co-container">
    <a href="{{ route('home') }}" class="co-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
    <h1 class="co-title">Xác Nhận Đơn Hàng</h1>

    <div class="co-grid">
        <div class="co-card">
            <div class="co-card-header">
                <div class="co-card-icon"><i class="fas fa-file-invoice"></i></div>
                <div>
                    <h2 class="co-card-title">Xác nhận tạo đơn hàng</h2>
                    <p class="co-card-desc">Mã đơn sẽ được tạo sau khi xác nhận</p>
                </div>
            </div>

            @if(!$accountsAvailable)
            <div class="co-error" style="margin: 18px 20px 0; padding: 16px 18px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; display: flex; align-items: flex-start; gap: 12px;">
                <i class="fas fa-exclamation-triangle" style="color: #dc2626; margin-top: 2px; font-size: 1.1rem;"></i>
                <div>
                    <div style="font-weight: 700; color: #991b1b; margin-bottom: 4px;">Tạm hết tài khoản trống</div>
                    <div style="font-size: 0.82rem; color: #b91c1c; line-height: 1.5;">
                        Hiện tại tất cả tài khoản đang được thuê. Vui lòng liên hệ admin qua
                        <a href="https://zalo.me/0777333763" style="color: #2563eb; font-weight: 700;">Zalo 0777333763</a>
                        để được hỗ trợ hoặc quay lại sau.
                    </div>
                </div>
            </div>
            @else
            <div class="co-alert">
                <i class="fas fa-exclamation-circle"></i>
                <span>Kiểm tra thông tin và nhấn nút bên dưới để tiếp tục</span>
            </div>
            @endif

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
                @if($accountsAvailable)
                <form method="post" action="{{ route('checkout.create') }}" id="checkoutForm">
                    @csrf
                    <input type="hidden" name="price_id" value="{{ $price->id }}">
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                    <button type="submit" class="co-btn-pay" id="submitBtn">
                        <i class="fas fa-check-circle"></i> Xác nhận và Tạo đơn hàng
                    </button>
                </form>
                @else
                <button class="co-btn-pay" disabled style="background: #9ca3af; cursor: not-allowed;">
                    <i class="fas fa-ban"></i> Tạm hết tài khoản — Liên hệ Admin
                </button>
                @endif
                <a href="{{ route('home') }}" class="co-back-link">← Quay về trang chủ</a>
            </div>
        </div>

        <div class="co-sidebar">
            <div class="co-sidebar-card">
                <div class="co-sidebar-icon security"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <div class="co-sidebar-title">Bảo mật 100%</div>
                    <div class="co-sidebar-desc">Mã hóa SSL toàn bộ giao dịch</div>
                </div>
            </div>
            <div class="co-sidebar-card">
                <div class="co-sidebar-icon auto"><i class="fas fa-bolt"></i></div>
                <div>
                    <div class="co-sidebar-title">Kích hoạt tự động</div>
                    <div class="co-sidebar-desc">Nhận tài khoản ngay sau thanh toán</div>
                </div>
            </div>
            <div class="co-sidebar-card">
                <div class="co-sidebar-icon support"><i class="fas fa-headset"></i></div>
                <div>
                    <div class="co-sidebar-title">Hỗ trợ 24/7</div>
                    <div class="co-sidebar-desc">Liên hệ Zalo bất cứ lúc nào</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
var form = document.getElementById('checkoutForm');
if (form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        grecaptcha.ready(function() {
            grecaptcha.execute('{{ config("services.recaptcha.site_key") }}', {action: 'create_order'}).then(function(token) {
                document.getElementById('recaptcha_token').value = token;
                form.submit();
            });
        });
    });
}
</script>
@endsection
