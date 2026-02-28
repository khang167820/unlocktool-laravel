@extends('admin.layouts.app')
@section('title', 'Account Management')
@section('page-title', 'Account Management')

@section('content')
<!-- Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div class="stat-info">
            <div class="stat-label">Total</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
            <div class="stat-label">Available</div>
            <div class="stat-value" style="color: #16a34a;">{{ $stats['available'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">🔒</div>
        <div class="stat-info">
            <div class="stat-label">Renting</div>
            <div class="stat-value" style="color: #f97316;">{{ $stats['renting'] }}</div>
        </div>
    </div>
</div>

<!-- Add Account -->
<div class="admin-card">
    <div class="admin-card-title">➕ Add New Account</div>
    <form action="{{ route('admin.accounts.add') }}" method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
        @csrf
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-input" required style="width: 200px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Password</label>
            <input type="text" name="password" class="form-input" required style="width: 200px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Note</label>
            <input type="text" name="note" class="form-input" style="width: 200px;">
        </div>
        <button type="submit" class="btn btn-success">Add Account</button>
    </form>
</div>

<!-- Batch Toggle Form -->
<form id="batchForm" action="{{ route('admin.accounts.batch') }}" method="POST">
    @csrf
    
    <!-- Accounts Table -->
    <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div class="admin-card-title" style="margin-bottom: 0;">📋 Accounts ({{ $stats['total'] }})</div>
            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Set selected accounts to Available?')">
                ✅ Set Selected to Available
            </button>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th><input type="checkbox" onclick="toggleAll(this)"></th>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Status</th>
                    <th>Note</th>
                    <th>Rental Info</th>
                    <th>Actions</th>
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
                            <span class="badge badge-active">Available</span>
                        @else
                            <span class="badge badge-inactive">Renting</span>
                        @endif
                    </td>
                    <td style="font-size: 12px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                        {{ $account->note ?? '—' }}
                    </td>
                    <td style="font-size: 11px;">
                        @if(isset($account->rental_order_code))
                            <div style="color: #3b82f6;">📋 {{ $account->rental_order_code }}</div>
                            @if(isset($account->rental_expires_at))
                                @php
                                    $expired = \Carbon\Carbon::parse($account->rental_expires_at)->isPast();
                                @endphp
                                <div style="color: {{ $expired ? '#ef4444' : '#10b981' }};">
                                    ⏰ {{ \Carbon\Carbon::parse($account->rental_expires_at)->format('d/m H:i') }}
                                    {{ $expired ? '(Expired)' : '' }}
                                </div>
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
                            <form action="{{ route('admin.accounts.delete', $account->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this account?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #64748b; padding: 40px;">No accounts</td>
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
</script>
@endsection
