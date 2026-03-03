@extends('admin.layouts.app')
@section('title', 'Báo cáo doanh thu')
@section('page-title', 'Báo cáo doanh thu')

@section('content')
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
            @forelse($dailyRevenue as $day)
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
