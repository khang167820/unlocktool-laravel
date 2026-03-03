@extends('admin.layouts.app')
@section('title', 'Nhật ký hệ thống')
@section('page-title', 'Nhật ký hệ thống')

@section('content')
<div class="admin-card">
    <div class="admin-card-title">📋 Laravel Log (5000 ký tự cuối)</div>
    <pre style="background: var(--bg-primary); padding: 16px; border-radius: 8px; font-size: 12px; color: var(--text-muted); overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; max-height: 600px; overflow-y: auto;">{{ $logContent ?: 'Không có dữ liệu log' }}</pre>
</div>
@endsection
