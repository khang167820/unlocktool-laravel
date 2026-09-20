@extends('admin.layouts.app')
@section('title', 'Đổi Pass Hàng Loạt')
@section('page-title', '🔑 Đổi Pass Hàng Loạt')
@section('content')
<style>
/* Stats */
.pr-stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px; }
.pr-stat-card { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
.pr-stat-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.pr-stat-icon.red { background: rgba(239,68,68,0.15); }
.pr-stat-icon.green { background: rgba(16,185,129,0.15); }
.pr-stat-icon.blue { background: rgba(59,130,246,0.15); }
.pr-stat-icon.amber { background: rgba(245,158,11,0.15); }
.pr-stat-label { font-size: 10px; color: var(--text-dimmed); text-transform: uppercase; letter-spacing: 0.5px; }
.pr-stat-value { font-size: 20px; font-weight: 700; color: var(--text-primary); }
/* Filter Tabs */
.pr-filter-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 16px; background: var(--bg-secondary); padding: 6px; border-radius: 10px; border: 1px solid var(--border-color); }
.pr-filter-tab { padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; color: var(--text-muted); display: flex; align-items: center; gap: 5px; transition: all 0.15s; border: none; cursor: pointer; background: none; }
.pr-filter-tab:hover { background: var(--bg-hover); color: var(--text-primary); }
.pr-filter-tab.active { background: #3b82f6; color: #fff; }
.pr-tab-count { padding: 0 5px; border-radius: 4px; font-size: 10px; font-weight: 700; }
.pr-tab-count.has-items { background: #ef4444; color: #fff; }
.pr-filter-tab.active .pr-tab-count { background: rgba(255,255,255,0.25); color: #fff; }
/* Table */
.pr-table-wrap { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; }
.pr-table { width: 100%; border-collapse: collapse; }
.pr-table th { padding: 10px 14px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; color: var(--text-dimmed); background: var(--bg-primary); border-bottom: 1px solid var(--border-color); letter-spacing: 0.5px; }
.pr-table td { padding: 10px 14px; font-size: 13px; color: var(--text-secondary); border-bottom: 1px solid var(--border-color); vertical-align: middle; }
.pr-table tr:last-child td { border-bottom: none; }
.pr-table tr:hover { background: var(--bg-hover); }
/* Account */
.pr-account-row { display: flex; align-items: center; gap: 8px; }
.pr-account-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pr-account-name { font-weight: 600; color: var(--text-primary); font-size: 13px; }
.pr-account-meta { font-size: 10px; color: var(--text-dimmed); }
/* Password */
.pr-pass { font-family: 'Courier New', monospace; }
.pr-pass-old { color: var(--text-muted); font-size: 12px; }
.pr-pass-arrow { color: var(--text-dimmed); margin: 0 4px; font-size: 11px; }
.pr-pass-new { color: #10b981; font-weight: 700; background: rgba(16,185,129,0.1); padding: 2px 8px; border-radius: 4px; font-size: 13px; }
/* Status */
.pr-status { font-size: 11px; font-weight: 500; white-space: nowrap; }
.pr-status-expired { color: #ef4444; }
.pr-status-soon { color: #f59e0b; animation: prBlink 1.5s infinite; }
@keyframes prBlink { 0%,100%{opacity:1;} 50%{opacity:0.5;} }
.pr-order-code { font-size: 10px; color: var(--text-dimmed); }
/* Buttons */
.pr-actions { display: flex; gap: 6px; align-items: center; }
.pr-copy-btn { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-secondary); cursor: pointer; transition: all 0.15s; white-space: nowrap; }
.pr-copy-btn:hover { border-color: #3b82f6; color: #3b82f6; }
.pr-copy-btn.copied { border-color: #10b981; color: #10b981; }
.pr-done-btn { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; border: none; cursor: pointer; background: #10b981; color: #fff; white-space: nowrap; transition: all 0.15s; }
.pr-done-btn:hover { background: #059669; }
.pr-done-btn.loading { opacity: 0.5; pointer-events: none; }
/* Misc */
.pr-empty { text-align: center; padding: 50px 20px; color: var(--text-dimmed); }
.pr-empty-icon { font-size: 40px; margin-bottom: 8px; opacity: 0.5; }
.pr-empty-text { font-size: 14px; color: var(--text-muted); }
.pr-row-synced { animation: prFadeOut 0.4s ease forwards; }
@keyframes prFadeOut { 0%{background:rgba(16,185,129,0.1);} 100%{opacity:0;height:0;padding:0;overflow:hidden;} }
.pr-batch-bar { display: flex; gap: 8px; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; }
.pr-batch-actions { display: flex; gap: 6px; }
.pr-batch-count { font-size: 12px; color: var(--text-muted); }
/* Date Edit */
.pr-expiry-row { display: flex; align-items: center; gap: 4px; margin-bottom: 2px; }
.pr-edit-date-btn { background: none; border: none; cursor: pointer; font-size: 11px; padding: 1px 4px; border-radius: 4px; opacity: 0.4; transition: all 0.15s; }
.pr-edit-date-btn:hover { opacity: 1; background: var(--bg-hover); }
.pr-date-edit { display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
.pr-date-input { padding: 3px 6px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 11px; background: var(--bg-primary); color: var(--text-primary); width: 120px; }
.pr-date-save, .pr-date-cancel { background: none; border: none; cursor: pointer; font-size: 12px; padding: 2px 4px; border-radius: 4px; transition: all 0.15s; }
.pr-date-save:hover { background: rgba(16,185,129,0.15); }
.pr-date-cancel:hover { background: rgba(239,68,68,0.15); }
/* Failed section */
.pr-failed-section { margin-top: 20px; background: var(--bg-secondary); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 14px; overflow: hidden; }
.pr-failed-header { padding: 12px 14px; font-size: 12px; font-weight: 700; color: #ef4444; border-bottom: 1px solid rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.06); display: flex; align-items: center; gap: 8px; }
.pr-failed-count { background: #ef4444; color: #fff; padding: 1px 7px; border-radius: 10px; font-size: 10px; font-weight: 700; }
.pr-failed-row { opacity: 1 !important; }
.pr-failed-row:hover { background: rgba(239, 68, 68, 0.04) !important; }
.pr-failed-icon { color: #ef4444; font-size: 14px; flex-shrink: 0; }
.pr-failed-msg { font-size: 11px; color: #f87171; margin-top: 2px; max-width: 300px; word-break: break-word; }
.pr-failed-attempts { font-size: 10px; color: var(--text-dimmed); }
.pr-retry-btn { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid rgba(239, 68, 68, 0.4); background: rgba(239, 68, 68, 0.08); color: #ef4444; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
.pr-retry-btn:hover { background: rgba(239, 68, 68, 0.15); border-color: #ef4444; }
.pr-status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
.pr-status-badge.failed { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
.pr-status-badge.attention { background: rgba(245, 158, 11, 0.12); color: #f59e0b; animation: prBlink 1.5s infinite; }
@media (max-width: 768px) { .pr-stats-grid { grid-template-columns: 1fr 1fr; } .pr-table-wrap { overflow-x: auto; } .pr-table { min-width: 650px; } }
@media (max-width: 480px) { .pr-stats-grid { grid-template-columns: 1fr; } }
</style>

<!-- Stats -->
<div class="pr-stats-grid">
    <div class="pr-stat-card"><div class="pr-stat-icon red">🔴</div><div><div class="pr-stat-label">Cần đổi pass</div><div class="pr-stat-value" id="stat-needs-sync">{{ $stats['needs_sync'] }}</div></div></div>
    <div class="pr-stat-card"><div class="pr-stat-icon amber">⏳</div><div><div class="pr-stat-label">Sắp hết hạn (45p)</div><div class="pr-stat-value">{{ $stats['expiring_soon'] }}</div></div></div>
    <div class="pr-stat-card"><div class="pr-stat-icon green">✅</div><div><div class="pr-stat-label">Đã đổi hôm nay</div><div class="pr-stat-value">{{ $stats['synced_today'] }}</div></div></div>
    <div class="pr-stat-card"><div class="pr-stat-icon red">❌</div><div><div class="pr-stat-label">Lỗi hôm nay</div><div class="pr-stat-value" style="{{ $stats['failed_today'] > 0 ? 'color:#ef4444;' : '' }}">{{ $stats['failed_today'] }}</div></div></div>
    <div class="pr-stat-card"><div class="pr-stat-icon blue">📊</div><div><div class="pr-stat-label">Tổng account</div><div class="pr-stat-value">{{ $stats['total_accounts'] }}</div></div></div>
</div>


<!-- ====== AGENT TỰ ĐỘNG ĐỔI PASS ====== -->
<div id="agent-section" style="margin-bottom:20px;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:14px;overflow:hidden;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:18px;">🤖</span>
            <span style="font-weight:700;font-size:14px;color:var(--text-primary);">Agent tự động đổi pass</span>
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            @if(session('agent_token'))
                <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.3);border-radius:8px;padding:8px 12px;display:flex;align-items:center;gap:8px;">
                    <code style="font-size:11px;color:#3b82f6;user-select:all;word-break:break-all;">{{ session('agent_token') }}</code>
                    <button onclick="navigator.clipboard.writeText('{{ session('agent_token') }}').then(()=>this.textContent='✅')" style="background:#3b82f6;color:#fff;border:none;padding:3px 8px;border-radius:4px;cursor:pointer;font-size:11px;">📋 Copy</button>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.password-rotation.agent.create') }}" style="display:inline;">
                @csrf
                <button type="submit" style="padding:6px 12px;border-radius:6px;font-size:11px;font-weight:600;border:1px solid rgba(59,130,246,0.3);background:rgba(59,130,246,0.1);color:#3b82f6;cursor:pointer;white-space:nowrap;">🔐 Tạo mã kết nối mới</button>
            </form>
            @if($accounts->count() > 0)
                <form method="POST" action="{{ route('admin.password-rotation.agent.queue') }}" style="display:inline;">
                    @csrf
                    @foreach($accounts as $acc)
                        <input type="hidden" name="account_ids[]" value="{{ $acc->id }}">
                    @endforeach
                    <button type="submit" style="padding:6px 12px;border-radius:6px;font-size:11px;font-weight:600;border:none;background:#10b981;color:#fff;cursor:pointer;white-space:nowrap;">▶ Đưa {{ $accounts->count() }} account vào Auto</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Job Queue Status --}}
    @php
        $jobs = \DB::table('password_rotation_jobs')
            ->join('accounts', 'password_rotation_jobs.account_id', '=', 'accounts.id')
            ->select('password_rotation_jobs.*', 'accounts.username')
            ->whereIn('password_rotation_jobs.status', ['queued', 'processing', 'attention'])
            ->orderBy('password_rotation_jobs.created_at')
            ->limit(20)
            ->get();
        $recentJobs = \DB::table('password_rotation_jobs')
            ->join('accounts', 'password_rotation_jobs.account_id', '=', 'accounts.id')
            ->select('password_rotation_jobs.*', 'accounts.username')
            ->whereIn('password_rotation_jobs.status', ['succeeded', 'failed'])
            ->whereDate('password_rotation_jobs.updated_at', today())
            ->orderByDesc('password_rotation_jobs.updated_at')
            ->limit(10)
            ->get();
        $agent = \DB::table('password_rotation_agents')->where('is_active', true)->first();
    @endphp

    @if($agent)
        <div style="padding:8px 16px;font-size:11px;color:var(--text-dimmed);border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:6px;">
            @if($agent->last_seen_at && \Carbon\Carbon::parse($agent->last_seen_at)->diffInMinutes(now()) < 2)
                <span style="width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                <span style="color:#10b981;font-weight:600;">Online</span>
            @else
                <span style="width:8px;height:8px;border-radius:50%;background:#94a3b8;display:inline-block;"></span>
                <span>Offline</span>
            @endif
            <span>· {{ $agent->name }}</span>
            @if($agent->last_seen_at)
                <span>· Lần cuối: {{ \Carbon\Carbon::parse($agent->last_seen_at)->locale('vi')->diffForHumans() }}</span>
            @endif
        </div>
    @endif

    @if($jobs->count() > 0 || $recentJobs->count() > 0)
        <div style="padding:10px 16px;">
            @if($jobs->count() > 0)
                <div style="font-size:11px;font-weight:600;color:var(--text-dimmed);margin-bottom:6px;">ĐANG XỬ LÝ ({{ $jobs->count() }})</div>
                @foreach($jobs as $j)
                    <div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:12px;">
                        @if($j->status === 'queued')
                            <span style="color:#f59e0b;" title="Đang chờ">⏳</span>
                        @elseif($j->status === 'processing')
                            <span style="color:#3b82f6;" title="Đang xử lý">⚙️</span>
                        @elseif($j->status === 'attention')
                            <span style="color:#ef4444;" title="Cần chú ý">⚠️</span>
                        @endif
                        <span style="font-weight:600;color:var(--text-primary);">{{ $j->username }}</span>
                        <span style="color:var(--text-dimmed);font-size:10px;">{{ $j->last_message ?? $j->status }}</span>
                    </div>
                @endforeach
            @endif
            @if($recentJobs->count() > 0)
                <div style="font-size:11px;font-weight:600;color:var(--text-dimmed);margin-top:8px;margin-bottom:4px;">HOÀN TẤT HÔM NAY</div>
                @foreach($recentJobs as $rj)
                    <div style="display:flex;align-items:center;gap:8px;padding:3px 0;font-size:11px;opacity:0.7;">
                        <span>{{ $rj->status === 'succeeded' ? '✅' : '❌' }}</span>
                        <span>{{ $rj->username }}</span>
                        <span style="color:var(--text-dimmed);font-size:10px;">{{ \Carbon\Carbon::parse($rj->updated_at)->format('H:i') }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    @else
        <div style="padding:12px 16px;font-size:12px;color:var(--text-dimmed);text-align:center;">Chưa có job nào. Bấm "Đưa ... account vào Auto" để bắt đầu.</div>
    @endif
</div>

<script>
// Auto-refresh agent section mỗi 10 giây
setInterval(() => {
    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newSection = doc.getElementById('agent-section');
            const oldSection = document.getElementById('agent-section');
            if (newSection && oldSection) {
                oldSection.innerHTML = newSection.innerHTML;
            }
        }).catch(() => {});
}, 10000);
</script>
<!-- ====== END AGENT ====== -->

<!-- Filter Tabs -->
@if(count($typeCounts) > 0)
<div class="pr-filter-tabs">
    <a href="{{ route('admin.password-rotation') }}" class="pr-filter-tab {{ !request('type') ? 'active' : '' }}">
        Tất cả @if($stats['needs_sync'] > 0)<span class="pr-tab-count has-items">{{ $stats['needs_sync'] }}</span>@endif
    </a>
    @foreach($typeCounts as $type => $count)
        <a href="{{ route('admin.password-rotation', ['type' => $type]) }}" class="pr-filter-tab {{ request('type') === $type ? 'active' : '' }}">
            {{ $typeLabels[$type] ?? $type }} <span class="pr-tab-count has-items">{{ $count }}</span>
        </a>
    @endforeach
</div>
@endif

<!-- Batch Actions -->
@if($accounts->count() > 0)
<div class="pr-batch-bar">
    <div class="pr-batch-count">{{ $accounts->count() }} account cần đổi password</div>
    <div class="pr-batch-actions">
        <button class="pr-copy-btn" onclick="copyAllPasswords()">📋 Copy tất cả</button>
        <form method="POST" action="{{ route('admin.password-rotation.generate-all') }}" style="display:inline;">@csrf
            <input type="hidden" name="type" value="{{ request('type') }}">
            <button type="submit" class="pr-copy-btn">🔄 Sinh lại</button>
        </form>
    </div>
</div>
@endif

<!-- Table -->
<div class="pr-table-wrap">
    @if($accounts->count() > 0)
    <table class="pr-table" id="pr-table">
        <thead><tr><th>Tài khoản</th><th>Password</th><th>Trạng thái</th><th style="text-align:right;">Hành động</th></tr></thead>
        <tbody>
            @foreach($accounts as $account)
            <tr id="pr-row-{{ $account->id }}">
                <td>
                    <div class="pr-account-row">
                        <div class="pr-account-dot" style="background: {{ $serviceColors[$account->type] ?? '#64748b' }};"></div>
                        <div>
                            <div class="pr-account-name">{{ $account->username }}</div>
                            <div class="pr-account-meta">{{ $typeLabels[$account->type] ?? $account->type }} · #{{ $account->id }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="pr-pass pr-pass-old">{{ $account->password }}</span>
                    <span class="pr-pass-arrow">→</span>
                    <span class="pr-pass pr-pass-new">{{ $account->new_password ?? '—' }}</span>
                </td>
                <td>
                    {{-- Ngày hết hạn TK (expires_at) --}}
                    <div class="pr-expiry-row" id="pr-expiry-{{ $account->id }}">
                        @if(isset($account->expires_at) && $account->expires_at)
                            @php $expiryDate = \Carbon\Carbon::parse($account->expires_at); @endphp
                            @if($expiryDate->isPast())
                                <span style="color:#ef4444; font-size:11px; font-weight:600;">⚠️ {{ $expiryDate->format('d/m/Y') }}</span>
                            @else
                                <span style="color:#10b981; font-size:11px; font-weight:600;">📅 {{ $expiryDate->format('d/m/Y') }}</span>
                            @endif
                        @else
                            <span style="color:#94a3b8; font-size:11px;">📅 —</span>
                        @endif
                        <button class="pr-edit-date-btn" onclick="showDateEdit({{ $account->id }}, '{{ $account->expires_at ? \Carbon\Carbon::parse($account->expires_at)->format('Y-m-d') : '' }}')" title="Sửa ngày hết hạn">✏️</button>
                    </div>
                    <div class="pr-date-edit" id="pr-date-edit-{{ $account->id }}" style="display:none;">
                        <input type="date" id="pr-date-input-{{ $account->id }}" value="{{ $account->expires_at ? \Carbon\Carbon::parse($account->expires_at)->format('Y-m-d') : '' }}" class="pr-date-input">
                        <button class="pr-date-save" onclick="saveDate({{ $account->id }})">💾</button>
                        <button class="pr-date-cancel" onclick="hideDateEdit({{ $account->id }})">✖</button>
                    </div>
                    {{-- Trạng thái rental (expired_at) --}}
                    @if($account->expired_at)
                        @php $exp = \Carbon\Carbon::parse($account->expired_at); @endphp
                        @if($exp->isPast())
                            <div class="pr-status pr-status-expired">🔴 {{ $exp->locale('vi')->diffForHumans() }}</div>
                        @else
                            <div class="pr-status pr-status-soon">⏳ Còn {{ $exp->locale('vi')->diffForHumans(now(), true) }}</div>
                        @endif
                        @if($account->order_code)<div class="pr-order-code">{{ $account->order_code }}</div>@endif
                    @endif
                </td>
                <td>
                    <div class="pr-actions" style="justify-content:flex-end;">
                        <button class="pr-copy-btn" data-orig="👤 TK" onclick="copyText(this, '{{ $account->username }}')">👤 TK</button>
                        <button class="pr-copy-btn" data-orig="📋 Pass" onclick="copyText(this, '{{ $account->new_password ?? $account->password }}')">📋 Pass</button>
                        <button class="pr-done-btn" onclick="markSynced({{ $account->id }}, this)">✅ Đã đổi</button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="pr-empty"><div class="pr-empty-icon">🎉</div><div class="pr-empty-text">Không có account nào cần đổi pass!</div></div>
    @endif
</div>

{{-- Failed / Attention Jobs --}}
@if($failedJobs->count() > 0)
<div class="pr-failed-section">
    <div class="pr-failed-header">
        ❌ Đổi pass thất bại
        <span class="pr-failed-count">{{ $failedJobs->count() }}</span>
    </div>
    <table class="pr-table">
        <thead>
            <tr>
                <th>Tài khoản</th>
                <th>Lỗi</th>
                <th>Trạng thái</th>
                <th style="text-align:right;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($failedJobs as $fj)
            <tr class="pr-failed-row" id="pr-failed-row-{{ $fj->job_id }}">
                <td>
                    <div class="pr-account-row">
                        <span class="pr-failed-icon">❌</span>
                        <div>
                            <div class="pr-account-name">{{ $fj->username }}</div>
                            <div class="pr-account-meta">{{ $typeLabels[$fj->type] ?? $fj->type }} · #{{ $fj->account_id }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="pr-failed-msg">{{ $fj->last_message ?: 'Agent không thể đổi mật khẩu.' }}</div>
                    @if($fj->attempts > 1)
                        <div class="pr-failed-attempts">Đã thử {{ $fj->attempts }} lần</div>
                    @endif
                </td>
                <td>
                    @if($fj->job_status === 'attention')
                        <span class="pr-status-badge attention">⚠️ Cần xử lý</span>
                    @else
                        <span class="pr-status-badge failed">❌ Thất bại</span>
                    @endif
                    <div style="font-size: 10px; color: var(--text-dimmed); margin-top: 2px;">
                        {{ $fj->completed_at ? \Carbon\Carbon::parse($fj->completed_at)->format('H:i') : \Carbon\Carbon::parse($fj->job_updated_at)->format('H:i') }}
                    </div>
                </td>
                <td>
                    <div class="pr-actions" style="justify-content:flex-end;">
                        <button class="pr-retry-btn" onclick="retryJob({{ $fj->job_id }}, this)">🔄 Thử lại</button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
<!-- Recently Synced Today -->
@if($recentlySynced->count() > 0)
<div style="margin-top:20px;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:14px;overflow:hidden;">
    <div style="padding:12px 14px;font-size:12px;font-weight:600;color:var(--text-dimmed);border-bottom:1px solid var(--border-color);">✅ Đã đổi hôm nay</div>
    <table class="pr-table"><tbody>
        @foreach($recentlySynced as $s)
        <tr style="opacity:0.6;">
            <td><span style="font-weight:600;color:var(--text-primary);">{{ $s->username }}</span> <span class="pr-account-meta">{{ $typeLabels[$s->type] ?? $s->type }} · #{{ $s->id }}</span></td>
            <td style="font-family:'Courier New',monospace;font-size:12px;">{{ $s->password }}</td>
            <td style="font-size:11px;color:var(--text-dimmed);text-align:right;">{{ $s->password_synced_at ? \Carbon\Carbon::parse($s->password_synced_at)->format('H:i') : '—' }}</td>
        </tr>
        @endforeach
    </tbody></table>
</div>
@endif

<script>
function copyText(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.dataset.orig || btn.innerHTML;
        btn.dataset.orig = orig;
        btn.innerHTML = '✅'; btn.classList.add('copied');
        setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('copied'); }, 1200);
    });
}

function copyAllPasswords() {
    let text = '';
    document.querySelectorAll('#pr-table tbody tr').forEach(row => {
        const name = row.querySelector('.pr-account-name')?.textContent?.trim() || '';
        const pass = row.querySelector('.pr-pass-new')?.textContent?.trim() || '';
        if (name && pass && pass !== '—') text += `${name} → ${pass}\n`;
    });
    if (text) navigator.clipboard.writeText(text).then(() => { alert('Đã copy tất cả!'); });
}

function markSynced(id, btn) {
    if (btn.classList.contains('loading')) return;
    btn.classList.add('loading'); btn.innerHTML = '⏳';
    fetch(`{{ url('/admin/password-rotation') }}/${id}/mark-synced`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
    }).then(r => r.json()).then(d => {
        if (d.success) {
            document.getElementById(`pr-row-${id}`).classList.add('pr-row-synced');
            setTimeout(() => document.getElementById(`pr-row-${id}`)?.remove(), 500);
            // Update stats counter
            const el = document.getElementById('stat-needs-sync');
            if (el) el.textContent = Math.max(0, parseInt(el.textContent) - 1);
        } else {
            btn.classList.remove('loading'); btn.innerHTML = '✅ Đã đổi';
            alert(d.error || 'Lỗi!');
        }
    }).catch(() => {
        btn.classList.remove('loading'); btn.innerHTML = '✅ Đã đổi';
        alert('Lỗi kết nối!');
    });
}

// === Date Edit Functions ===

function retryJob(jobId, btn) {
    if (btn.disabled) return;
    btn.disabled = true; btn.innerHTML = '⏳ Đang thử...';
    fetch('{{ url("/admin/password-rotation/agent/retry-job") }}/' + jobId, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const row = document.getElementById('pr-failed-row-' + jobId);
            row.style.background = 'rgba(16, 185, 129, 0.1)';
            btn.innerHTML = '✅ Đã đưa lại hàng đợi';
            btn.style.borderColor = '#10b981'; btn.style.color = '#10b981';
            setTimeout(() => row.remove(), 1500);
        } else {
            btn.disabled = false; btn.innerHTML = '🔄 Thử lại';
            alert(d.error || 'Không thể thử lại!');
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '🔄 Thử lại'; alert('Lỗi kết nối!'); });
}

function showDateEdit(id, currentDate) {
    document.getElementById(`pr-expiry-${id}`).style.display = 'none';
    document.getElementById(`pr-date-edit-${id}`).style.display = 'flex';
    if (currentDate) document.getElementById(`pr-date-input-${id}`).value = currentDate;
}

function hideDateEdit(id) {
    document.getElementById(`pr-date-edit-${id}`).style.display = 'none';
    document.getElementById(`pr-expiry-${id}`).style.display = 'flex';
}

function saveDate(id) {
    const input = document.getElementById(`pr-date-input-${id}`);
    const newDate = input.value;
    
    fetch(`{{ url('/admin/accounts') }}/${id}/update`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ expires_at: newDate || null })
    }).then(r => r.json()).then(d => {
        if (d.success) {
            // Update display
            const expiryRow = document.getElementById(`pr-expiry-${id}`);
            if (newDate) {
                const parts = newDate.split('-');
                const formatted = `${parts[2]}/${parts[1]}/${parts[0]}`;
                const today = new Date();
                const expDate = new Date(newDate);
                const isPast = expDate < today;
                expiryRow.innerHTML = `
                    <span style="color:${isPast ? '#ef4444' : '#10b981'}; font-size:11px; font-weight:600;">${isPast ? '⚠️' : '📅'} ${formatted}</span>
                    <button class="pr-edit-date-btn" onclick="showDateEdit(${id}, '${newDate}')" title="Sửa ngày hết hạn">✏️</button>
                `;
            } else {
                expiryRow.innerHTML = `
                    <span style="color:#94a3b8; font-size:11px;">📅 —</span>
                    <button class="pr-edit-date-btn" onclick="showDateEdit(${id}, '')" title="Sửa ngày hết hạn">✏️</button>
                `;
            }
            hideDateEdit(id);
        } else {
            alert(d.error || 'Lỗi cập nhật!');
        }
    }).catch(() => alert('Lỗi kết nối!'));
}
</script>
@endsection
