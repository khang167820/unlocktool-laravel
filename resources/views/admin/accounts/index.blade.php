@extends('admin.layouts.app')
@section('title', 'Quản lý Tài khoản')
@section('page-title', 'Quản lý Tài khoản')

@section('content')
<!-- Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div class="stat-info">
            <div class="stat-label">Tổng cộng</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
            <div class="stat-label">Chờ thuê</div>
            <div class="stat-value" style="color: #16a34a;">{{ $stats['available'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">🔒</div>
        <div class="stat-info">
            <div class="stat-label">Đang thuê</div>
            <div class="stat-value" style="color: #f97316;">{{ $stats['renting'] }}</div>
        </div>
    </div>
</div>

<!-- Add Account -->
<div class="admin-card">
    <div class="admin-card-title">➕ Thêm tài khoản mới</div>
    <form action="{{ route('admin.accounts.add') }}" method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
        @csrf
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-input" required style="width: 200px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Mật khẩu</label>
            <input type="text" name="password" class="form-input" required style="width: 200px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Ghi chú</label>
            <input type="text" name="note" class="form-input" style="width: 200px;">
        </div>
        <button type="submit" class="btn btn-success">Thêm</button>
    </form>
</div>

<!-- Batch Toggle Form -->
<form id="batchForm" action="{{ route('admin.accounts.batch') }}" method="POST">
    @csrf
    
    <!-- Accounts Table -->
    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div class="admin-card-title" style="margin-bottom: 0;">📋 Tài khoản ({{ $stats['total'] }})</div>
            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Đặt các tài khoản đã chọn thành Chờ thuê?')">
                ✅ Đặt thành Chờ thuê
            </button>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th><input type="checkbox" onclick="toggleAll(this)"></th>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Mật khẩu</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                    <th>Thời gian thuê</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $account->id }}"></td>
                    <td>#{{ $account->id }}</td>
                    <td><strong>{{ $account->username }}</strong></td>
                    <td style="font-family: monospace; font-size: 12px;">{{ $account->password }}</td>
                    <td>
                        @if($account->is_available)
                            <span class="badge badge-active">Chờ thuê</span>
                        @else
                            <span class="badge badge-inactive">Đang thuê</span>
                        @endif
                    </td>
                    <td style="font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                        {{ $account->note ?? '—' }}
                    </td>
                    <td style="font-size: 11px; min-width: 160px;">
                        @if(!$account->is_available && isset($account->rental_order_code))
                            <div style="color: #3b82f6; margin-bottom: 2px;">📋 {{ $account->rental_order_code }}</div>
                            @if(isset($account->rental_expires_at))
                                @php
                                    $expiresAt = \Carbon\Carbon::parse($account->rental_expires_at);
                                    $expired = $expiresAt->isPast();
                                    $isoDate = $expiresAt->toIso8601String();
                                @endphp
                                @if($expired)
                                    <div style="color: #ef4444; font-weight: 600;">
                                        ⏰ Hết hạn!
                                        <div style="font-size: 10px; color: #94a3b8;">{{ $expiresAt->format('d/m H:i') }}</div>
                                    </div>
                                @else
                                    <div class="countdown-timer" data-expires="{{ $isoDate }}" style="color: #10b981; font-weight: 600;">
                                        ⏳ Đang tính...
                                    </div>
                                @endif
                            @endif
                        @elseif($account->is_available)
                            @php
                                $waitingSince = isset($account->sorting_expires_at) 
                                    ? \Carbon\Carbon::parse($account->sorting_expires_at) 
                                    : (isset($account->created_at) ? \Carbon\Carbon::parse($account->created_at) : null);
                            @endphp
                            @if($waitingSince)
                                <div class="waiting-timer" data-since="{{ $waitingSince->toIso8601String() }}" style="color: #8b5cf6; font-size: 11px;">
                                    🕐 Đang tính...
                                </div>
                            @else
                                <span style="color: #8b5cf6;">🕐 Mới thêm</span>
                            @endif
                        @else
                            <span style="color: #64748b;">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 4px;">
                            <form action="{{ route('admin.accounts.toggle', $account->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <input type="hidden" name="status" value="{{ $account->is_available ? 'renting' : 'available' }}">
                                <button type="submit" class="btn btn-sm {{ $account->is_available ? 'btn-danger' : 'btn-success' }}">
                                    {{ $account->is_available ? '🔒' : '✅' }}
                                </button>
                            </form>
                            <a href="{{ route('admin.accounts.edit', $account->id) }}" class="btn btn-sm btn-secondary">✏️</a>
                            <form action="{{ route('admin.accounts.delete', $account->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Xóa tài khoản này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 40px;">Chưa có tài khoản nào</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

@if($accounts->hasPages())
<div class="pagination">{{ $accounts->links() }}</div>
@endif

<script>
function toggleAll(el) {
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = el.checked);
}

// Live countdown timers
function updateCountdowns() {
    document.querySelectorAll('.countdown-timer').forEach(el => {
        const expires = new Date(el.dataset.expires);
        const now = new Date();
        const diff = expires - now;
        
        if (diff <= 0) {
            el.innerHTML = '<span style="color: #ef4444; font-weight: 600;">⏰ Hết hạn!</span>';
            el.style.color = '#ef4444';
            return;
        }
        
        const days = Math.floor(diff / 86400000);
        const hours = Math.floor((diff % 86400000) / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        let timeStr = '';
        if (days > 0) {
            timeStr = `${days}d ${hours}h ${minutes}m`;
        } else if (hours > 0) {
            timeStr = `${hours}h ${minutes}m ${seconds}s`;
        } else {
            timeStr = `${minutes}m ${seconds}s`;
        }
        
        // Color: green > 1h, yellow < 1h, red < 10m
        let color = '#10b981';
        if (diff < 600000) color = '#ef4444';       // < 10 min
        else if (diff < 3600000) color = '#f59e0b';  // < 1 hour
        
        el.style.color = color;
        el.innerHTML = `⏳ <strong>${timeStr}</strong>`;
    });
}

updateCountdowns();
setInterval(updateCountdowns, 1000);

// Live waiting timers for "Chờ thuê" accounts
function updateWaitingTimers() {
    document.querySelectorAll('.waiting-timer').forEach(el => {
        const since = new Date(el.dataset.since);
        const now = new Date();
        const diff = now - since;

        if (diff < 0) {
            el.innerHTML = '🕐 Mới thêm';
            return;
        }

        const days = Math.floor(diff / 86400000);
        const hours = Math.floor((diff % 86400000) / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);

        let timeStr = '';
        if (days > 0) {
            timeStr = `${days}d ${hours}h ${minutes}m`;
        } else if (hours > 0) {
            timeStr = `${hours}h ${minutes}m`;
        } else {
            timeStr = `${minutes}m`;
        }

        el.innerHTML = `🕐 Chờ <strong>${timeStr}</strong>`;
    });
}

updateWaitingTimers();
setInterval(updateWaitingTimers, 60000);
</script>
@endsection
