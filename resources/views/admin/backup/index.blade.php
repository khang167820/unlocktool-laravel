@extends('admin.layouts.app')

@section('title', 'Sao lưu & Phục hồi')
@section('page-title', 'Sao lưu & Phục hồi')

@section('content')
<!-- Current Backups -->
<div class="admin-card">
    <div class="admin-card-title">💾 Backup Database</div>
    <p style="color: #64748b; font-size: 13px; margin-bottom: 16px;">
        Tạo backup database để phục hồi khi cần thiết
    </p>
    
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <form action="{{ route('admin.backup.create') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-primary" onclick="return confirm('Tạo backup database?')">
                ➕ Tạo Backup Mới
            </button>
        </form>
    </div>
</div>

<!-- Backup List -->
<div class="admin-card">
    <div class="admin-card-title">📁 Danh sách Backup</div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Tên file</th>
                <th>Kích thước</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($backups as $backup)
            <tr>
                <td>
                    <span style="font-family: monospace; font-size: 12px;">{{ $backup['name'] }}</span>
                </td>
                <td>{{ $backup['size'] }}</td>
                <td style="font-size: 12px;">{{ $backup['date'] }}</td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <a href="{{ route('admin.backup.download', $backup['name']) }}" class="btn btn-sm btn-secondary">
                            📥 Tải
                        </a>
                        <form action="{{ route('admin.backup.delete', $backup['name']) }}" method="POST" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xóa backup này?')">
                                🗑️
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; padding: 40px; color: #64748b;">
                    <div style="font-size: 48px; margin-bottom: 12px;">📂</div>
                    <p>Chưa có backup nào</p>
                    <p style="font-size: 12px;">Tạo backup để bảo vệ dữ liệu của bạn</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Danger Zone -->
<div class="admin-card" style="border-color: #ef4444;">
    <div class="admin-card-title" style="color: #ef4444;">⚠️ Vùng Nguy hiểm</div>
    
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <form action="{{ route('admin.system.optimize') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Optimize database?')">
                ⚡ Optimize Tables
            </button>
        </form>
        
        <form action="{{ route('admin.system.clear-cache') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Xóa tất cả cache?')">
                🗑️ Xóa Cache
            </button>
        </form>
    </div>
    
    <p style="color: #94a3b8; font-size: 12px; margin-top: 16px;">
        ⚠️ Các thao tác này có thể ảnh hưởng đến hiệu suất tạm thời. Chỉ sử dụng khi cần thiết.
    </p>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-top: 16px;">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="alert alert-danger" style="margin-top: 16px;">{{ session('error') }}</div>
@endif
@endsection
