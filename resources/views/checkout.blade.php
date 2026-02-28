@extends('layouts.app')

@section('title', 'Xác nhận đơn hàng - UnlockTool.us')

@section('head')
<script src="https://www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}"></script>
@endsection

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh;">
<div class="overlay" style="max-width: 600px; margin: 20px;">
    <h2 class="mb-4 text-center">Xác nhận thuê tài khoản</h2>
    <div class="card">
        <div class="card-body">
            <p><strong>Gói thuê:</strong> {{ $packageName }}</p>
            <p><strong>Giá tiền:</strong> {{ $formattedPrice }}</p>
            <p class="text-muted">Bạn sẽ được cấp tài khoản sau khi thanh toán thành công.<br>Vui lòng kiểm tra kỹ thông tin trước khi tiếp tục.</p>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="post" action="{{ route('checkout.create') }}" id="checkoutForm">
                @csrf
                <input type="hidden" name="price_id" value="{{ $price->id }}">
                <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                <button type="submit" class="btn btn-success btn-block" id="submitBtn">✅ Xác nhận và thanh toán</button>
                <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-block mt-2">⬅ Quay lại</a>
            </form>
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
    btn.innerHTML = '⏳ Đang xử lý...';
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ env("RECAPTCHA_SITE_KEY") }}', {action: 'create_order'}).then(function(token) {
            document.getElementById('recaptcha_token').value = token;
            document.getElementById('checkoutForm').submit();
        });
    });
});
</script>
@endsection
