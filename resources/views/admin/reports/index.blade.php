@extends('admin.layouts.app')
@section('title', 'Báo cáo Doanh thu')
@section('page-title', 'Báo cáo Doanh thu')

@section('content')
<style>
.report-tabs { display: flex; gap: 0; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
.report-tab {
    padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
    background: var(--bg-hover); color: var(--text-muted); border: 1px solid var(--border-color);
    text-decoration: none; transition: all 0.2s;
}
.report-tab:first-child { border-radius: 8px 0 0 8px; }
.report-tab:hover { background: var(--bg-secondary); color: var(--text-primary); text-decoration: none; }
.report-tab.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.report-tab-sep { padding: 8px 12px; background: var(--bg-hover); border: 1px solid var(--border-color); border-left: none; color: var(--text-dimmed); font-size: 12px; }
.report-date { padding: 7px 12px; font-size: 13px; background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); border-left: none; outline: none; }
.report-date:last-of-type { border-radius: 0; }
.report-btn-go { padding: 8px 16px; font-size: 13px; font-weight: 600; background: #3b82f6; color: #fff; border: 1px solid #3b82f6; border-left: none; border-radius: 0 8px 8px 0; cursor: pointer; }

.report-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
.report-stat {
    padding: 20px 22px; border-radius: 12px; color: #fff; font-weight: 600;
    display: flex; flex-direction: column; gap: 4px;
}
.report-stat.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.report-stat.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.report-stat.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
.report-stat.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.report-stat-label { font-size: 12px; opacity: 0.85; }
.report-stat-value { font-size: 24px; font-weight: 800; }

.chart-container {
    background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px;
    padding: 20px; margin-bottom: 24px; overflow: hidden;
}
.chart-title { font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 16px; }
.chart-canvas { width: 100%; height: 280px; }

.section-title { font-size: 15px; font-weight: 700; color: var(--text-primary); margin-bottom: 14px; }

.pkg-bar-bg { background: var(--bg-hover); border-radius: 4px; height: 20px; overflow: hidden; flex: 1; }
.pkg-bar { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #3b82f6, #14b8a6); transition: width 0.4s ease; }
.pkg-pct { font-size: 12px; font-weight: 600; color: var(--text-muted); min-width: 45px; text-align: right; }

@media (max-width: 768px) {
    .report-stats { grid-template-columns: repeat(2, 1fr); }
    .report-tabs { gap: 4px; }
}
@media (max-width: 480px) {
    .report-stats { grid-template-columns: 1fr; }
}
</style>

{{-- Date Filter Tabs --}}
<form action="{{ route('admin.reports') }}" method="GET" class="report-tabs" id="reportForm">
    @foreach([
        'today' => 'Hôm nay',
        'yesterday' => 'Hôm qua', 
        'week' => 'Tuần này',
        'month' => 'Tháng này',
        'year' => 'Năm này'
    ] as $key => $label)
        <a href="{{ route('admin.reports', ['range' => $key]) }}" 
           class="report-tab {{ $range === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
    <span class="report-tab-sep">|</span>
    <input type="hidden" name="range" value="custom" id="rangeInput">
    <input type="date" name="from" class="report-date" value="{{ $startDate->format('Y-m-d') }}">
    <span class="report-tab-sep" style="border-left: none; padding: 8px 6px;">→</span>
    <input type="date" name="to" class="report-date" value="{{ $endDate->format('Y-m-d') }}">
    <button type="submit" class="report-btn-go" onclick="document.getElementById('rangeInput').value='custom'">Xem</button>
</form>

{{-- Summary Stats --}}
<div class="report-stats">
    <div class="report-stat blue">
        <div class="report-stat-label">Tổng doanh thu</div>
        <div class="report-stat-value">{{ number_format($totalRevenue, 0, ',', '.') }}đ</div>
    </div>
    <div class="report-stat teal">
        <div class="report-stat-label">Tổng đơn hàng</div>
        <div class="report-stat-value">{{ number_format($totalOrders) }}</div>
    </div>
    <div class="report-stat orange">
        <div class="report-stat-label">Trung bình / đơn</div>
        <div class="report-stat-value">{{ number_format($avgPerOrder, 0, ',', '.') }}đ</div>
    </div>
    <div class="report-stat purple">
        <div class="report-stat-label">Users mới</div>
        <div class="report-stat-value">{{ $uniqueIps }}</div>
    </div>
</div>

{{-- Revenue Chart --}}
<div class="chart-container">
    <div class="chart-title">📈 Biểu đồ Doanh thu</div>
    <canvas id="revenueChart" class="chart-canvas"></canvas>
</div>

{{-- Revenue by Package --}}
<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-title">🔥 Doanh thu theo Gói thuê</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Gói thuê</th>
                <th>Số đơn</th>
                <th>Doanh thu</th>
                <th>Tỷ lệ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($packageRevenue as $pkg)
            <tr>
                <td><strong style="color: #3b82f6;">{{ $pkg->label }}</strong></td>
                <td>{{ $pkg->order_count }}</td>
                <td style="color: #10b981; font-weight: 600;">{{ number_format($pkg->total, 0, ',', '.') }}đ</td>
                <td style="width: 30%;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="pkg-bar-bg">
                            <div class="pkg-bar" style="width: {{ $pkg->percentage }}%;"></div>
                        </div>
                        <span class="pkg-pct">{{ $pkg->percentage }}%</span>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: var(--text-dimmed); padding: 30px;">Chưa có dữ liệu</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Top Customers --}}
<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-title">🏆 Top Khách hàng</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Khách hàng</th>
                <th>IP Address</th>
                <th>Số đơn</th>
                <th>Tổng chi tiêu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topCustomers as $i => $customer)
            <tr>
                <td>
                    @if($i === 0)
                        <span style="font-size: 18px;">🥇</span>
                    @elseif($i === 1)
                        <span style="font-size: 18px;">🥈</span>
                    @elseif($i === 2)
                        <span style="font-size: 18px;">🥉</span>
                    @else
                        {{ $i + 1 }}
                    @endif
                </td>
                <td><strong>Guest</strong></td>
                <td style="font-family: monospace; font-size: 12px; color: var(--text-muted);">{{ $customer->ip_address ?? '—' }}</td>
                <td>{{ $customer->order_count }}</td>
                <td style="color: #10b981; font-weight: 700;">{{ number_format($customer->total_spent, 0, ',', '.') }}đ</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: var(--text-dimmed); padding: 30px;">Chưa có dữ liệu</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Daily Revenue Table --}}
<div class="admin-card">
    <div class="admin-card-title">📊 Doanh thu hàng ngày</div>
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

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    @php
        $chartLabels = $dailyRevenue->pluck('date')->map(function($d) { return \Carbon\Carbon::parse($d)->format('d/m'); })->values();
        $chartData = $dailyRevenue->pluck('total')->values();
    @endphp
    var labels = @json($chartLabels);
    var data = @json($chartData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: data,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.08)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleFont: { size: 13, weight: '600' },
                    bodyFont: { size: 13 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return new Intl.NumberFormat('vi-VN').format(ctx.parsed.y) + 'đ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(128,128,128,0.1)' },
                    ticks: {
                        font: { size: 11 },
                        callback: function(v) {
                            if (v >= 1000000) return (v/1000000).toFixed(1) + ' M';
                            if (v >= 1000) return (v/1000) + ' N';
                            return v;
                        }
                    }
                },
                x: {
                    grid: { color: 'rgba(128,128,128,0.05)' },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
});
</script>
@endsection
