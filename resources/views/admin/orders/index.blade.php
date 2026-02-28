@extends('admin.layouts.app')
@section('title', 'Orders Management')
@section('page-title', 'Orders Management')

@section('content')
<div class="filter-bar">
    <form action="{{ route('admin.orders') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="text" name="search" class="form-input" placeholder="Search tracking code..." value="{{ request('search') }}" style="width: 200px;">
        <select name="status" class="form-select" style="width: 180px;">
            <option value="">All Status</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending ({{ $statusCounts['pending'] ?? 0 }})</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid ({{ $statusCounts['paid'] ?? 0 }})</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed ({{ $statusCounts['completed'] ?? 0 }})</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled ({{ $statusCounts['cancelled'] ?? 0 }})</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('admin.orders') }}" class="btn btn-secondary">Reset</a>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tracking Code</th>
                <th>Hours</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>#{{ $order->id }}</td>
                <td><strong>{{ $order->tracking_code }}</strong></td>
                <td>{{ $order->hours }}h</td>
                <td style="color: #10b981; font-weight: 600;">{{ number_format($order->amount, 0, ',', '.') }}đ</td>
                <td>
                    @if($order->status === 'pending')
                        <span class="badge badge-pending">Pending</span>
                    @elseif($order->status === 'paid')
                        <span class="badge badge-paid">Paid</span>
                    @elseif($order->status === 'completed')
                        <span class="badge badge-completed">Completed</span>
                    @else
                        <span class="badge badge-cancelled">{{ $order->status }}</span>
                    @endif
                </td>
                <td>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                <td>
                    <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <select name="status" class="form-select" style="width: 120px; font-size: 11px; padding: 4px 8px;" onchange="this.form.submit()">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">No orders found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($orders->hasPages())
<div class="pagination">{{ $orders->links() }}</div>
@endif
@endsection
