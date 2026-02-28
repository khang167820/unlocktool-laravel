@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
            <div class="stat-label">Today Revenue</div>
            <div class="stat-value">{{ number_format($todayRevenue, 0, ',', '.') }}đ</div>
            <div class="stat-sub">Week: {{ number_format($weekRevenue, 0, ',', '.') }}đ • Month: {{ number_format($monthRevenue, 0, ',', '.') }}đ</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">📦</div>
        <div class="stat-info">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value">{{ number_format($totalOrders) }}</div>
            <div class="stat-sub">Today: {{ $todayOrders }} • Pending: {{ $pendingOrders }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">👤</div>
        <div class="stat-info">
            <div class="stat-label">Accounts</div>
            <div class="stat-value">{{ $totalAccounts }}</div>
            <div class="stat-sub">Available: {{ $availableAccounts }} • Renting: {{ $rentingAccounts }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">✍️</div>
        <div class="stat-info">
            <div class="stat-label">Blog Posts</div>
            <div class="stat-value">{{ $blogStats['total'] }}</div>
            <div class="stat-sub">Published: {{ $blogStats['published'] }}</div>
        </div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: none;">
        <div class="stat-info" style="width: 100%; text-align: center;">
            <div class="stat-label" style="color: #92400e;">Pending</div>
            <div class="stat-value" style="color: #d97706;">{{ $pendingOrders }}</div>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); border: none;">
        <div class="stat-info" style="width: 100%; text-align: center;">
            <div class="stat-label" style="color: #1e40af;">Paid</div>
            <div class="stat-value" style="color: #2563eb;">{{ $paidOrders }}</div>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); border: none;">
        <div class="stat-info" style="width: 100%; text-align: center;">
            <div class="stat-label" style="color: #166534;">Completed</div>
            <div class="stat-value" style="color: #16a34a;">{{ $completedOrders }}</div>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #f1f5f9, #e2e8f0); border: none;">
        <div class="stat-info" style="width: 100%; text-align: center;">
            <div class="stat-label" style="color: #475569;">Total</div>
            <div class="stat-value" style="color: #1e293b;">{{ $totalOrders }}</div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-title">📋 Recent Orders</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Tracking Code</th>
                <th>Service</th>
                <th>Hours</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentOrders as $order)
            <tr>
                <td><strong>{{ $order->tracking_code }}</strong></td>
                <td>{{ $order->service_type ?? 'Unlocktool' }}</td>
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
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #64748b;">No orders yet</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div style="text-align: center; margin-top: 16px;">
        <a href="{{ route('admin.orders') }}" class="btn btn-secondary">View All Orders →</a>
    </div>
</div>
@endsection
