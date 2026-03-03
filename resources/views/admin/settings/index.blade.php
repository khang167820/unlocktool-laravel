@extends('admin.layouts.app')
@section('title', 'Cài đặt')
@section('page-title', 'Cài đặt')

@section('content')
<div class="admin-card" style="max-width: 600px;">
    <div class="admin-card-title">⚙️ Cài đặt hệ thống</div>
    <div class="form-group">
        <label class="form-label">App URL</label>
        <input type="text" class="form-input" value="{{ config('app.url') }}" disabled>
    </div>
    <div class="form-group">
        <label class="form-label">Môi trường</label>
        <input type="text" class="form-input" value="{{ config('app.env') }}" disabled>
    </div>
    <div class="form-group">
        <label class="form-label">Chế độ Debug</label>
        <input type="text" class="form-input" value="{{ config('app.debug') ? 'BẬT' : 'TẮT' }}" disabled>
    </div>
    <div class="form-group">
        <label class="form-label">PHP Version</label>
        <input type="text" class="form-input" value="{{ phpversion() }}" disabled>
    </div>
    <div class="form-group">
        <label class="form-label">Laravel Version</label>
        <input type="text" class="form-input" value="{{ app()->version() }}" disabled>
    </div>
    
    <div style="margin-top: 24px;">
        <a href="/fix-cache.php" target="_blank" class="btn btn-primary">🔄 Xóa Cache</a>
    </div>
</div>
@endsection
