@extends('admin.layouts.app')

@section('title', 'Đơn thiếu tiền')
@section('page-title', 'Đơn hàng thiếu tiền')

@section('content')
<div class="admin-card">
    <div class="admin-card-title">Danh sách đơn hàng có vấn đề</div>
    <p style="color: #64748b; font-size: 13px; margin-bottom: 16px;">
        Các đơn hàng chưa thanh toán đủ hoặc cần xử lý đặc biệt
    </p>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Gói thuê</th>
                    <th>Số tiền</th>
                    <th>Ghi chú</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>
                        <span style="font-family: monospace; color: #3b82f6;">{{ $order->tracking_code ?? 'N/A' }}</span>
                    </td>
                    <td>{{ $order->service_type ?? 'N/A' }}</td>
                    <td style="color: #f59e0b; font-weight: 600;">{{ number_format($order->amount ?? 0) }}đ</td>
                    <td style="font-size: 12px; max-width: 200px; color: #94a3b8;">
                        {{ $order->notes ?? '—' }}
                    </td>
                    <td style="font-size: 12px;">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.orders') }}?search={{ $order->tracking_code }}" class="btn btn-sm btn-secondary">
                            Xem chi tiết
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                        <div style="font-size: 48px; margin-bottom: 12px;">✅</div>
                        <p>Không có đơn thiếu tiền</p>
                        <p style="font-size: 12px;">Tất cả đơn hàng đều ổn!</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($orders->hasPages())
        <div class="pagination">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
