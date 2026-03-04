@extends('admin.layouts.app')
@section('title', 'Báo cáo doanh thu')
@section('page-title', 'Báo cáo doanh thu')

@section('content')
@php
    $totalRevenue = $dailyRevenue->sum('total');
    $totalOrders = $dailyRevenue->sum('count');
    $avgDaily = $dailyRevenue->count() > 0 ? $totalRevenue / $dailyRevenue->count() : 0;
    $todayRevenue = $dailyRevenue->last();
@endphp

{{-- Summary Stats --}}
<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">💰</div>
        <div>
            <div class="stat-value">{{ number_format($totalRevenue, 0, ',', '.') }}đ</div>
            <div class="stat-label">Tổng doanh thu (30 ngày)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">📦</div>
        <div>
            <div class="stat-value">{{ number_format($totalOrders) }}</div>
            <div class="stat-label">Tổng đơn hàng</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">📊</div>
        <div>
            <div class="stat-value">{{ number_format($avgDaily, 0, ',', '.') }}đ</div>
            <div class="stat-label">Trung bình / ngày</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(139,92,246,0.15); color: #8b5cf6;">🔥</div>
        <div>
            <div class="stat-value">{{ $todayRevenue ? number_format($todayRevenue->total, 0, ',', '.') . 'đ' : '0đ' }}</div>
            <div class="stat-label">Hôm nay ({{ $todayRevenue ? $todayRevenue->count . ' đơn' : '0 đơn' }})</div>
        </div>
    </div>
</div>

{{-- Daily Revenue Table --}}
<div class="admin-card">
    <div class="admin-card-title">📊 Doanh thu hàng ngày (30 ngày gần nhất)</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Ngày</th>
                <th>Đơn hàng</th>
                <th>Doanh thu</th>
                <th>Biểu đồ</th>
            </tr>
        </thead>
        <tbody>
            @php $maxRevenue = $dailyRevenue->max('total') ?: 1; @endphp
            @forelse($dailyRevenue->reverse() as $day)
            <tr>
                <td><strong>{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</strong></td>
                <td>{{ $day->count }}</td>
                <td style="color: #10b981; font-weight: 600;">{{ number_format($day->total, 0, ',', '.') }}đ</td>
                <td style="width: 40%;">
                    <div style="background: var(--bg-hover); border-radius: 4px; height: 24px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #3b82f6, #10b981); height: 100%; width: {{ ($day->total / $maxRevenue) * 100 }}%; border-radius: 4px; transition: width 0.3s;"></div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #64748b; padding: 40px;">Chưa có dữ liệu doanh thu</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
