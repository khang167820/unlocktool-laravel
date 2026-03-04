@extends('admin.layouts.app')
@section('title', 'Quản lý Tài khoản')
@section('page-title', 'Quản lý Tài khoản')

@section('content')
<!-- Add Account Form -->
<div class="admin-card" style="margin-bottom: 20px; padding: 20px;">
    <form action="{{ route('admin.accounts.add') }}" method="POST" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: end;">
        @csrf
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 14px; margin-bottom: 6px;">Tên đăng nhập</label>
            <input type="text" name="username" class="form-input" required style="width: 200px; font-size: 15px; padding: 10px 12px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 14px; margin-bottom: 6px;">Mật khẩu</label>
            <input type="text" name="password" class="form-input" required style="width: 200px; font-size: 15px; padding: 10px 12px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 14px; margin-bottom: 6px;">Loại</label>
            <input type="text" class="form-input" value="Unlocktool" readonly style="width: 150px; font-size: 15px; padding: 10px 12px; opacity: 0.7;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 14px; margin-bottom: 6px;">Ngày gia hạn</label>
            <input type="date" name="expires_at" class="form-input" style="width: 170px; font-size: 15px; padding: 10px 12px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" style="font-size: 14px; margin-bottom: 6px;">Ghi chú</label>
            <input type="text" name="note" class="form-input" style="width: 180px; font-size: 15px; padding: 10px 12px;">
        </div>
        <button type="submit" class="btn btn-primary" style="height: 44px; font-size: 15px; padding: 0 20px;">+ Thêm</button>
    </form>
</div>

<!-- Stats & Action Buttons -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
    <div style="font-size: 16px; color: var(--text-muted);">
        Tổng <strong style="color: var(--text-primary);">{{ $stats['total'] }}</strong> tài khoản 
        &nbsp;·&nbsp; 
        <span style="color: #10b981;">{{ $stats['available'] }} chờ thuê</span>
        &nbsp;·&nbsp;
        <span style="color: #f97316;">{{ $stats['renting'] }} đang thuê</span>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="https://unlocktool.us" target="_blank" class="btn" style="background: #10b981; color: white; font-size: 14px; padding: 8px 16px; border-radius: 6px; text-decoration: none;">
            🌐 Unlocktool.us
        </a>
        <form id="batchForm" action="{{ route('admin.accounts.batch') }}" method="POST" style="display: inline;">
            @csrf
        </form>
        <button type="submit" form="batchForm" class="btn" style="background: #ef4444; color: white; font-size: 14px; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;" onclick="return confirm('Đặt các tài khoản đã chọn thành Chờ thuê?')">
            🔄 Lưu trạng thái
        </button>
    </div>
</div>

<!-- Accounts Table -->
<div class="admin-card" style="padding: 0; overflow-x: auto;">
    <table class="admin-table" style="margin: 0; font-size: 15px;">
        <thead>
            <tr>
                <th style="width: 50px; text-align: center; font-size: 14px; padding: 14px 10px;">ID</th>
                <th style="min-width: 240px; font-size: 14px; padding: 14px 10px;">Tài khoản / Mật khẩu</th>
                <th style="min-width: 180px; font-size: 14px; padding: 14px 10px;">Trạng thái & Thời gian</th>
                <th style="width: 120px; font-size: 14px; padding: 14px 10px;">Ghi chú</th>
                <th style="width: 140px; text-align: center; font-size: 14px; padding: 14px 10px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accounts as $account)
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="text-align: center; font-weight: 700; color: var(--text-muted); font-size: 16px; padding: 16px 10px;">{{ $account->id }}</td>
                <td style="padding: 14px 10px;">
                    <div style="margin-bottom: 4px;">
                        <span style="color: var(--text-muted); font-size: 14px;">TK:</span>
                        <strong style="color: var(--text-primary); font-size: 16px;">{{ $account->username }}</strong>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <span style="color: var(--text-muted); font-size: 14px;">MK:</span>
                        <span style="font-family: monospace; color: var(--text-secondary); font-size: 15px;">{{ $account->password }}</span>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" class="btn-copy" onclick="copyText('{{ $account->username }}\n{{ $account->password }}', this)" style="padding: 4px 14px; font-size: 13px; border-radius: 5px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-secondary); cursor: pointer; font-weight: 500;">
                            Copy
                        </button>
                        <a href="{{ route('admin.accounts.edit', $account->id) }}" style="padding: 4px 14px; font-size: 13px; border-radius: 5px; border: none; background: #f97316; color: white; text-decoration: none; display: inline-flex; align-items: center; font-weight: 500;">
                            Sửa
                        </a>
                    </div>
                </td>
                <td style="padding: 14px 10px;">
                    @if(!$account->is_available)
                        <span style="display: inline-block; padding: 5px 14px; border-radius: 5px; font-size: 14px; font-weight: 600; background: #f97316; color: white; margin-bottom: 6px;">
                            Đang thuê
                        </span>
                        @if(isset($account->rental_expires_at))
                            @php
                                $expiresAt = \Carbon\Carbon::parse($account->rental_expires_at);
                                $expired = $expiresAt->isPast();
                                $isoDate = $expiresAt->toIso8601String();
                            @endphp
                            @if($expired)
                                <div style="color: #ef4444; font-size: 14px; font-weight: 600;">
                                    ⚠ HẾT HẠN
                                    <div style="font-size: 12px; color: var(--text-dimmed); margin-top: 2px;">{{ $expiresAt->format('d/m/Y H:i') }}</div>
                                </div>
                            @else
                                <div class="countdown-timer" data-expires="{{ $isoDate }}" style="font-size: 14px; font-weight: 600; color: #10b981;">
                                    ⏳ Đang tính...
                                </div>
                            @endif
                        @elseif(isset($account->rental_order_code))
                            <div style="font-size: 13px; color: #3b82f6;">📋 {{ $account->rental_order_code }}</div>
                        @endif
                    @else
                        <span style="display: inline-block; padding: 5px 14px; border-radius: 5px; font-size: 14px; font-weight: 600; background: #10b981; color: white; margin-bottom: 6px;">
                            Chờ thuê
                        </span>
                        @php
                            $waitingSince = isset($account->sorting_expires_at) 
                                ? \Carbon\Carbon::parse($account->sorting_expires_at) 
                                : (isset($account->created_at) ? \Carbon\Carbon::parse($account->created_at) : null);
                        @endphp
                        @if($waitingSince)
                            <div class="waiting-timer" data-since="{{ $waitingSince->toIso8601String() }}" style="color: #8b5cf6; font-size: 13px;">
                                🕐 Đang tính...
                            </div>
                        @else
                            <span style="color: #8b5cf6; font-size: 13px;">🕐 Mới thêm</span>
                        @endif
                    @endif
                </td>
                <td style="font-size: 14px; color: var(--text-muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; padding: 14px 10px;">
                    {{ $account->note ?? '—' }}
                </td>
                <td style="text-align: center; padding: 14px 10px;">
                    <div style="display: flex; gap: 8px; justify-content: center; align-items: center; flex-wrap: nowrap;">
                        <form action="{{ route('admin.accounts.toggle', $account->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <input type="hidden" name="status" value="{{ $account->is_available ? 'renting' : 'available' }}">
                            <input type="hidden" name="ids[]" value="{{ $account->id }}" form="batchForm">
                            <button type="submit" style="padding: 8px 18px; font-size: 14px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; background: #3b82f6; color: white; white-space: nowrap;">
                                Chuyển TT
                            </button>
                        </form>
                        <!-- Password changed indicator dot: green=đã đổi pass, orange=chưa đổi -->
                        <span title="{{ $account->password_changed ? 'Đã đổi pass ✅' : 'Chưa đổi pass ⚠' }}" style="width: 16px; height: 16px; border-radius: 50%; display: inline-block; flex-shrink: 0; border: 2px solid rgba(255,255,255,0.2); cursor: help;
                            {{ $account->password_changed ? 'background: #10b981;' : 'background: #f97316;' }}">
                        </span>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: var(--text-dimmed); padding: 40px; font-size: 16px;">Chưa có tài khoản nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($accounts->hasPages())
<div class="pagination" style="margin-top: 16px;">{{ $accounts->links() }}</div>
@endif

<script>
// Copy text to clipboard
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.textContent;
        btn.textContent = '✓';
        btn.style.background = '#10b981';
        btn.style.borderColor = '#10b981';
        btn.style.color = 'white';
        setTimeout(() => {
            btn.textContent = original;
            btn.style.background = '';
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 1500);
    });
}

// Live countdown timers for "Đang thuê" accounts
function updateCountdowns() {
    document.querySelectorAll('.countdown-timer').forEach(el => {
        const expires = new Date(el.dataset.expires);
        const now = new Date();
        const diff = expires - now;
        
        if (diff <= 0) {
            el.innerHTML = '<span style="color: #ef4444; font-weight: 600;">⚠ HẾT HẠN</span>';
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
        
        let color = '#10b981';
        if (diff < 600000) color = '#ef4444';
        else if (diff < 3600000) color = '#f59e0b';
        
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
