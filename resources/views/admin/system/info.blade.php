@extends('admin.layouts.app')

@section('title', 'Thông tin Hệ thống')
@section('page-title', 'Thông tin Hệ thống')

@section('content')
<!-- Server Info Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb); border: none;">
        <div class="stat-info" style="width: 100%; text-align: center;">
            <div class="stat-label" style="color: rgba(255,255,255,0.8);">PHP Version</div>
            <div class="stat-value" style="color: #fff; font-size: 20px;">{{ phpversion() }}</div>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669); border: none;">
        <div class="stat-info" style="width: 100%; text-align: center;">
            <div class="stat-label" style="color: rgba(255,255,255,0.8);">Laravel</div>
            <div class="stat-value" style="color: #fff; font-size: 20px;">{{ app()->version() }}</div>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); border: none;">
        <div class="stat-info" style="width: 100%; text-align: center;">
            <div class="stat-label" style="color: rgba(255,255,255,0.8);">Database</div>
            <div class="stat-value" style="color: #fff; font-size: 20px;">{{ config('database.default') }}</div>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none;">
        <div class="stat-info" style="width: 100%; text-align: center;">
            <div class="stat-label" style="color: rgba(255,255,255,0.8);">Environment</div>
            <div class="stat-value" style="color: #fff; font-size: 20px;">{{ app()->environment() }}</div>
        </div>
    </div>
</div>

<!-- Database Stats -->
<div class="admin-card">
    <div class="admin-card-title">📊 Thống kê Database</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        @foreach($dbStats as $table => $count)
        <div style="background: var(--bg-darker, #0f172a); padding: 16px; border-radius: 12px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: #3b82f6;">{{ number_format($count) }}</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">{{ $table }}</div>
        </div>
        @endforeach
    </div>
</div>

<!-- Disk Usage -->
<div class="admin-card">
    <div class="admin-card-title">💾 Dung lượng Lưu trữ</div>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <div style="background: var(--bg-darker, #0f172a); padding: 20px; border-radius: 12px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #94a3b8;">Tổng dung lượng</span>
                <span style="color: #f1f5f9; font-weight: 600;">{{ $diskInfo['total'] }}</span>
            </div>
            <div style="height: 8px; background: #334155; border-radius: 4px; overflow: hidden;">
                <div style="width: {{ $diskInfo['used_percent'] }}%; height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6);"></div>
            </div>
        </div>
        <div style="background: var(--bg-darker, #0f172a); padding: 20px; border-radius: 12px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #94a3b8;">Đã sử dụng</span>
                <span style="color: #10b981; font-weight: 600;">{{ $diskInfo['used'] }}</span>
            </div>
        </div>
        <div style="background: var(--bg-darker, #0f172a); padding: 20px; border-radius: 12px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: #94a3b8;">Còn trống</span>
                <span style="color: #f59e0b; font-weight: 600;">{{ $diskInfo['free'] }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <div class="admin-card-title">⚡ Thao tác Nhanh</div>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <form action="{{ route('admin.system.clear-cache') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Xóa tất cả cache?')">
                🗑️ Xóa Cache
            </button>
        </form>
        <form action="{{ route('admin.system.clear-views') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Xóa compiled views?')">
                📄 Xóa Views Cache
            </button>
        </form>
        <form action="{{ route('admin.system.optimize') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Optimize database?')">
                ⚡ Optimize Tables
            </button>
        </form>
    </div>
</div>

<!-- Server Info Table -->
<div class="admin-card">
    <div class="admin-card-title">🖥️ Thông tin Server</div>
    <table class="admin-table">
        <tbody>
            <tr><td style="width: 200px; font-weight: 600;">Server Software</td><td>{{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</td></tr>
            <tr><td style="font-weight: 600;">Server Name</td><td>{{ $_SERVER['SERVER_NAME'] ?? 'N/A' }}</td></tr>
            <tr><td style="font-weight: 600;">Document Root</td><td style="font-family: monospace; font-size: 12px;">{{ $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' }}</td></tr>
            <tr><td style="font-weight: 600;">PHP Memory Limit</td><td>{{ ini_get('memory_limit') }}</td></tr>
            <tr><td style="font-weight: 600;">Max Upload Size</td><td>{{ ini_get('upload_max_filesize') }}</td></tr>
            <tr><td style="font-weight: 600;">Max Execution Time</td><td>{{ ini_get('max_execution_time') }}s</td></tr>
            <tr><td style="font-weight: 600;">Timezone</td><td>{{ config('app.timezone') }}</td></tr>
            <tr><td style="font-weight: 600;">Cache Driver</td><td>{{ config('cache.default') }}</td></tr>
            <tr><td style="font-weight: 600;">Session Driver</td><td>{{ config('session.driver') }}</td></tr>
        </tbody>
    </table>
</div>

<!-- Extensions -->
<div class="admin-card">
    <div class="admin-card-title">🔧 PHP Extensions</div>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        @foreach($extensions as $ext)
        <span style="background: var(--bg-darker, #0f172a); padding: 6px 12px; border-radius: 6px; font-size: 12px; color: #94a3b8;">
            {{ $ext }}
        </span>
        @endforeach
    </div>
</div>

@if(session('success'))
<script>alert('{{ session('success') }}')</script>
@endif
@endsection
