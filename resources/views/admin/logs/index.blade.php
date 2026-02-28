@extends('admin.layouts.app')
@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
<div class="admin-card">
    <div class="admin-card-title">📋 Laravel Log (last 5000 chars)</div>
    <pre style="background: var(--bg-primary); padding: 16px; border-radius: 8px; font-size: 12px; color: var(--text-muted); overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; max-height: 600px; overflow-y: auto;">{{ $logContent ?: 'No log data' }}</pre>
</div>
@endsection
