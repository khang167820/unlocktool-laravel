@extends('admin.layouts.app')
@section('title', 'Quản Lý Đơn Hàng')
@section('page-title', 'Quản Lý Đơn Hàng')

@section('content')
<div class="filter-bar">
    <form action="{{ route('admin.orders') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="text" name="search" class="form-input" placeholder="Tìm mã đơn hàng..." value="{{ request('search') }}" style="width: 200px;">
        <select name="status" class="form-select" style="width: 180px;">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ xử lý ({{ $statusCounts['pending'] ?? 0 }})</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Đã thanh toán ({{ $statusCounts['paid'] ?? 0 }})</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn thành ({{ $statusCounts['completed'] ?? 0 }})</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy ({{ $statusCounts['cancelled'] ?? 0 }})</option>
        </select>
        <input type="text" name="account" class="form-input" placeholder="Tên tài khoản cấp..." value="{{ request('account') }}" style="width: 200px;">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('admin.orders') }}" class="btn btn-secondary">Đặt lại</a>
    </form>
</div>

<div class="admin-card">
    <div style="overflow-x: auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã đơn</th>
                <th>Dịch vụ</th>
                <th>Thời gian</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
                <th>Tài khoản cấp</th>
                <th>Ngày tạo</th>
                <th>Người thuê</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td style="color: var(--text-dimmed);">#{{ $order->id }}</td>
                <td><strong>{{ $order->tracking_code }}</strong></td>
                <td>
                    <span style="color: #3b82f6; font-weight: 600;">Unlocktool</span>
                </td>
                <td>{{ $order->hours }} giờ</td>
                <td>
                    <span style="color: #10b981; font-weight: 700;">{{ number_format($order->amount, 0, ',', '.') }}đ</span>
                </td>
                <td>
                    @if($order->status === 'pending')
                        <span class="badge badge-pending">Chờ xử lý</span>
                    @elseif($order->status === 'paid')
                        <span class="badge badge-paid">Đã thanh toán</span>
                    @elseif($order->status === 'completed')
                        <span class="badge badge-completed">Hoàn thành</span>
                    @else
                        <span class="badge badge-cancelled">{{ $order->status }}</span>
                    @endif
                </td>
                <td>
                    @if($order->account)
                        <div>
                            <span style="color: #3b82f6; cursor: pointer;" title="{{ $order->account->username }}">
                                🔑 <strong>{{ $order->account->username }}</strong>
                            </span>
                        </div>
                        <div style="font-size: 13px; color: var(--text-muted); font-family: monospace;">
                            {{ $order->assigned_password ?? $order->account->password }}
                        </div>
                    @else
                        <span style="color: var(--text-dimmed);">—</span>
                    @endif
                </td>
                <td style="white-space: nowrap;">{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                <td>
                    @if($order->ip_address)
                        <span style="font-size: 12px; font-family: monospace; color: var(--text-muted);">{{ $order->ip_address }}</span>
                    @else
                        <span style="color: var(--text-dimmed);">—</span>
                    @endif
                </td>
                <td>
                    <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <select name="status" class="form-select" style="width: 130px; font-size: 12px; padding: 6px 8px;" onchange="this.form.submit()">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                            <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; color: #64748b; padding: 40px;">Không tìm thấy đơn hàng nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($orders->hasPages())
<div class="pagination">{{ $orders->links() }}</div>
@endif
@endsection
