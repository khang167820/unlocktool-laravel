@extends('admin.layouts.app')

@section('title', 'Sửa tài khoản #' . $account->id)
@section('page-title', 'Sửa tài khoản #' . $account->id)

@section('content')
<style>
.edit-form { max-width: 800px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
@media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
.input-group { display: flex; gap: 8px; }
.input-group .form-input { flex: 1; }
.btn-copy { padding: 10px 16px; background: var(--bg-hover); color: var(--text-secondary); border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; font-size: 12px; white-space: nowrap; }
.btn-copy:hover { background: var(--border-color); color: var(--text-primary); }
.btn-suggest { padding: 10px 16px; background: #8b5cf6; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 12px; white-space: nowrap; }
.btn-suggest:hover { background: #7c3aed; }
.btn-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 30px; }
.form-note { font-size: 11px; color: var(--text-dimmed); margin-top: 4px; }
.readonly-input { background: var(--bg-hover) !important; }
</style>

<a href="{{ route('admin.accounts') }}" style="color: var(--text-muted); margin-bottom: 20px; display: inline-block;">← Quay lại danh sách</a>

<div class="admin-card edit-form">
    <form action="{{ route('admin.accounts.update', $account->id) }}" method="POST" id="updateForm">
        @csrf
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tên đăng nhập</label>
                <div class="input-group">
                    <input type="text" name="username" id="username" class="form-input readonly-input" 
                           value="{{ $account->username }}" readonly>
                    <button type="button" class="btn-copy" onclick="copyText('username', this)">Copy TK</button>
                </div>
                <div class="form-note">Không cho phép sửa tên đăng nhập</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Mật khẩu</label>
                <div class="input-group">
                    <input type="text" name="password" id="password" class="form-input" 
                           value="{{ $account->password }}">
                    <button type="button" class="btn-suggest" onclick="suggestPassword(this)">Đề xuất</button>
                    <button type="button" class="btn-copy" onclick="copyText('password', this)">Copy MK</button>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Loại</label>
                <input type="text" class="form-input readonly-input" value="{{ $account->type ?? 'Unlocktool' }}" readonly>
                <input type="hidden" name="type" value="{{ $account->type ?? 'Unlocktool' }}">
            </div>
            
            <div class="form-group">
                <label class="form-label">Ghi chú (nội dung)</label>
                <input type="text" name="note" class="form-input" 
                       value="{{ $account->note ?? '' }}" placeholder="Nhập ghi chú">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Ngày hết hạn TK</label>
                <input type="date" name="expires_at" class="form-input" 
                       value="{{ isset($account->expires_at) && $account->expires_at ? \Carbon\Carbon::parse($account->expires_at)->format('Y-m-d') : '' }}"
                       style="{{ isset($account->expires_at) && $account->expires_at && \Carbon\Carbon::parse($account->expires_at)->isPast() ? 'border-color: #ef4444; background: #fef2f2;' : '' }}">
                @if(isset($account->expires_at) && $account->expires_at && \Carbon\Carbon::parse($account->expires_at)->isPast())
                    <div class="form-note" style="color: #ef4444; font-weight: 600;">⚠️ TK đã hết hạn!</div>
                @elseif(isset($account->expires_at) && $account->expires_at)
                    <div class="form-note" style="color: #22c55e;">Còn {{ \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($account->expires_at)) }} ngày</div>
                @endif
            </div>
            
        </div>
        
        <div class="btn-row">
            <!-- Chờ thuê (Set Available) -->
            <button type="button" class="btn" style="background: #4ade80;" onclick="setStatus('available')">
                ☑️ Chờ thuê
            </button>
            
            <!-- Đổi pass -->
            <button type="button" class="btn" style="background: #fb923c;" onclick="changePassword()">
                🔐 Đổi pass
            </button>
            
            <!-- Cập nhật -->
            <button type="button" class="btn" style="background: #60a5fa;" onclick="this.closest('form').submit()">
                💾 Cập nhật
            </button>
            
            <!-- Quay lại -->
            <a href="{{ route('admin.accounts') }}" class="btn" style="background: #9ca3af;">
                ← Quay lại
            </a>
            
            <!-- Xóa -->
            <button type="button" class="btn" style="background: #f87171;" onclick="deleteAccount()">
                🗑️ Xóa
            </button>
            
            <!-- Reset TG (Thời gian) -->
            <button type="button" class="btn" style="background: #facc15;" onclick="resetTime()">
                ⏱️ Reset TG
            </button>
        </div>
    </form>
    
    <!-- Hidden forms for other actions -->
    <form action="{{ route('admin.accounts.toggle', $account->id) }}" method="POST" id="toggleForm" style="display:none;">
        @csrf
        <input type="hidden" name="status" id="toggleStatus">
    </form>
    
    <form action="{{ route('admin.accounts.change-pass', $account->id) }}" method="POST" id="changePassForm" style="display:none;">
        @csrf
        <input type="hidden" name="password" id="newPassword">
    </form>
    
    <form action="{{ route('admin.accounts.reset-tg', $account->id) }}" method="POST" id="resetTGForm" style="display:none;">
        @csrf
    </form>
    
    <form action="{{ route('admin.accounts.delete', $account->id) }}" method="POST" id="deleteForm" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
function copyText(inputId, btn) {
    const input = document.getElementById(inputId);
    navigator.clipboard.writeText(input.value);
    
    const originalText = btn.innerText;
    btn.innerText = '✅';
    btn.style.color = '#10b981';
    btn.style.borderColor = '#10b981';
    
    setTimeout(() => {
        btn.innerText = originalText;
        btn.style.color = '';
        btn.style.borderColor = '';
    }, 1500);
}

function suggestPassword(btn) {
    const randomNum = Math.floor(Math.random() * 900) + 100;
    const password = 'Unlock' + randomNum;
    document.getElementById('password').value = password;
    
    const originalText = btn.innerText;
    btn.innerText = '✅';
    btn.style.background = '#10b981';
    
    setTimeout(() => {
        btn.innerText = originalText;
        btn.style.background = '';
    }, 1500);
}

function setStatus(status) {
    const newPass = document.getElementById('password').value;
    const noteValue = document.querySelector('input[name="note"]').value;
    const form = document.getElementById('toggleForm');
    
    let pwInput = form.querySelector('input[name="password"]');
    if (!pwInput) {
        pwInput = document.createElement('input');
        pwInput.type = 'hidden';
        pwInput.name = 'password';
        form.appendChild(pwInput);
    }
    pwInput.value = newPass;
    
    let noteInput = form.querySelector('input[name="note"]');
    if (!noteInput) {
        noteInput = document.createElement('input');
        noteInput.type = 'hidden';
        noteInput.name = 'note';
        form.appendChild(noteInput);
    }
    noteInput.value = noteValue;
    
    document.getElementById('toggleStatus').value = status;
    form.submit();
}

function changePassword() {
    const newPass = document.getElementById('password').value;
    if (!newPass) {
        alert('Vui lòng nhập mật khẩu mới!');
        return;
    }
    document.getElementById('newPassword').value = newPass;
    document.getElementById('changePassForm').submit();
}

function resetTime() {
    if (confirm('Reset thời gian của tài khoản này?\n- Đang thuê → Hết hạn ngay\n- Chờ thuê → Reset thời gian chờ về 0')) {
        document.getElementById('resetTGForm').submit();
    }
}

function deleteAccount() {
    if (confirm('⚠️ Xác nhận XÓA tài khoản này? Hành động không thể hoàn tác!')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endsection
