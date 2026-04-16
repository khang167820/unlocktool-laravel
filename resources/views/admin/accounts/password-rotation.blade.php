@extends('admin.layouts.app')
@section('title', 'Đổi Pass Hàng Loạt')
@section('page-title', '🔑 Đổi Pass Hàng Loạt')
@section('content')
<style>
/* Stats */
.pr-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
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
@media (max-width: 768px) { .pr-stats-grid { grid-template-columns: 1fr 1fr; } .pr-table-wrap { overflow-x: auto; } .pr-table { min-width: 650px; } }
@media (max-width: 480px) { .pr-stats-grid { grid-template-columns: 1fr; } }
</style>

<!-- Stats -->
<div class="pr-stats-grid">
    <div class="pr-stat-card"><div class="pr-stat-icon red">🔴</div><div><div class="pr-stat-label">Cần đổi pass</div><div class="pr-stat-value" id="stat-needs-sync">{{ $stats['needs_sync'] }}</div></div></div>
    <div class="pr-stat-card"><div class="pr-stat-icon amber">⏳</div><div><div class="pr-stat-label">Sắp hết hạn (45p)</div><div class="pr-stat-value">{{ $stats['expiring_soon'] }}</div></div></div>
    <div class="pr-stat-card"><div class="pr-stat-icon green">✅</div><div><div class="pr-stat-label">Đã đổi hôm nay</div><div class="pr-stat-value">{{ $stats['synced_today'] }}</div></div></div>
    <div class="pr-stat-card"><div class="pr-stat-icon blue">📊</div><div><div class="pr-stat-label">Tổng account</div><div class="pr-stat-value">{{ $stats['total_accounts'] }}</div></div></div>
</div>

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
        <thead><tr><th>Tài khoản</th><th>Hạn TK</th><th>Password</th><th>Trạng thái</th><th style="text-align:right;">Hành động</th></tr></thead>
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
                    @if(isset($account->expires_at) && $account->expires_at)
                        @php $expiryDate = \Carbon\Carbon::parse($account->expires_at); @endphp
                        @if($expiryDate->isPast())
                            <div style="color:#ef4444; font-size:11px; font-weight:600;">⚠️ Hết hạn TK!</div>
                            <div style="font-size:10px; color:#94a3b8;">{{ $expiryDate->format('d/m/Y') }}</div>
                        @else
                            <div style="color:#10b981; font-size:11px; font-weight:600;">{{ $expiryDate->format('d/m/Y') }}</div>
                            <div style="font-size:10px; color:#64748b;">Còn {{ now()->diffInDays($expiryDate) }}d</div>
                        @endif
                    @else
                        <span style="color:#cbd5e1; font-size:11px;">—</span>
                    @endif
                </td>
                <td>
                    <span class="pr-pass pr-pass-old">{{ $account->password }}</span>
                    <span class="pr-pass-arrow">→</span>
                    <span class="pr-pass pr-pass-new">{{ $account->new_password ?? '—' }}</span>
                </td>
                <td>
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
</script>
@endsection
