@extends('admin.layouts.app')

@section('title', 'Kết quả Tìm kiếm')
@section('page-title', 'Kết quả Tìm kiếm')

@section('content')
<div style="margin-bottom: 20px;">
    <span style="color: #94a3b8;">Kết quả tìm kiếm cho: </span>
    <span style="font-weight: 600; color: #3b82f6;">"{{ $query }}"</span>
</div>

@php
    $hasResults = false;
    foreach ($results as $group) {
        if ($group->isNotEmpty()) { $hasResults = true; break; }
    }
@endphp

@if(!$hasResults)
<div class="admin-card" style="text-align: center; padding: 60px;">
    <div style="font-size: 64px; margin-bottom: 16px;">🔍</div>
    <h3 style="color: #f1f5f9; margin-bottom: 8px;">Không tìm thấy kết quả</h3>
    <p style="color: #64748b;">Thử tìm kiếm với từ khóa khác</p>
</div>
@else

<!-- Orders Results -->
@if($results['orders']->isNotEmpty())
<div class="admin-card">
    <div class="admin-card-title">📦 Đơn hàng ({{ $results['orders']->count() }})</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã đơn</th>
                <th>Dịch vụ</th>
                <th>Thời gian</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results['orders'] as $order)
            <tr>
                <td style="color: #94a3b8;">#{{ $order->id }}</td>
                <td><strong>{{ $order->tracking_code }}</strong></td>
                <td>
                    <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;">{{ $order->service_type ?? 'Unlocktool' }}</span>
                </td>
                <td>{{ $order->hours ?? '-' }} giờ</td>
                <td style="color: #10b981; font-weight: 600;">{{ number_format($order->amount ?? 0, 0, ',', '.') }}đ</td>
                <td>
                    @if($order->status === 'pending')
                        <span class="badge badge-pending">Chờ TT</span>
                    @elseif($order->status === 'paid')
                        <span class="badge badge-paid">Đã TT</span>
                    @elseif($order->status === 'completed')
                        <span class="badge badge-completed">Hoàn thành</span>
                    @else
                        <span class="badge badge-cancelled">{{ $order->status }}</span>
                    @endif
                </td>
                <td style="font-size: 12px;">{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Accounts Results -->
@if($results['accounts']->isNotEmpty())
<div class="admin-card">
    <div class="admin-card-title">🔑 Tài khoản ({{ $results['accounts']->count() }})</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Loại</th>
                <th>Username</th>
                <th>Mật khẩu</th>
                <th>Trạng thái</th>
                <th>Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results['accounts'] as $account)
            <tr>
                <td style="color: #94a3b8;">#{{ $account->id }}</td>
                <td><span class="badge badge-active">{{ $account->type }}</span></td>
                <td><strong style="color: #1e40af;">{{ $account->username }}</strong></td>
                <td style="font-family: monospace; font-size: 12px; color: #64748b;">{{ $account->password ?? '***' }}</td>
                <td>
                    @if($account->is_available)
                        <span class="badge badge-completed">Còn trống</span>
                    @else
                        <span class="badge badge-pending">Đang thuê</span>
                    @endif
                </td>
                <td style="font-size: 12px; color: #94a3b8;">{{ Str::limit($account->note, 50) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Coupons Results -->
@if(isset($results['coupons']) && $results['coupons']->isNotEmpty())
<div class="admin-card">
    <div class="admin-card-title">🎫 Mã giảm giá ({{ $results['coupons']->count() }})</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã</th>
                <th>Loại</th>
                <th>Giá trị</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results['coupons'] as $coupon)
            <tr>
                <td style="color: #94a3b8;">#{{ $coupon->id }}</td>
                <td><strong style="font-family: monospace;">{{ $coupon->code }}</strong></td>
                <td>{{ $coupon->discount_type ?? $coupon->type ?? 'N/A' }}</td>
                <td style="color: #10b981; font-weight: 600;">
                    @if(($coupon->discount_type ?? $coupon->type) === 'percent')
                        {{ $coupon->discount_value ?? $coupon->value }}%
                    @else
                        {{ number_format($coupon->discount_value ?? $coupon->value ?? 0) }}đ
                    @endif
                </td>
                <td>
                    @if($coupon->is_active)
                        <span class="badge badge-completed">Hoạt động</span>
                    @else
                        <span class="badge badge-inactive">Đã tắt</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endif
@endsection
