@extends('admin.layouts.app')
@section('title', 'Quản Lý Đơn Hàng')
@section('page-title', 'Quản Lý Đơn Hàng')

@section('content')
<div class="filter-bar">
    <form action="{{ route('admin.orders') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="text" name="search" class="form-input" placeholder="Tìm mã đơn hàng..." value="{{ request('search') }}" style="width: 200px;">
        <select name="status" class="form-select" style="width: 180px;">
            <option value="">Tất cả trạng thái</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ xử lý ({{ $statusCounts['pending'] ?? 0 }})</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Đã thanh toán ({{ $statusCounts['paid'] ?? 0 }})</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn thành ({{ $statusCounts['completed'] ?? 0 }})</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy ({{ $statusCounts['cancelled'] ?? 0 }})</option>
        </select>
        <input type="text" name="account" class="form-input" placeholder="Tên tài khoản cấp..." value="{{ request('account') }}" style="width: 200px;">
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="{{ route('admin.orders') }}" class="btn btn-secondary">Đặt lại</a>
    </form>
</div>

<div class="admin-card">
    <div style="overflow-x: auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã đơn</th>
                <th>Dịch vụ</th>
                <th>Thời gian</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
                <th>Tài khoản cấp</th>
                <th>Ngày tạo</th>
                <th>Người thuê</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td style="color: var(--text-dimmed);">#{{ $order->id }}</td>
                <td><strong>{{ $order->tracking_code }}</strong></td>
                <td>
                    <span style="color: #3b82f6; font-weight: 600;">Unlocktool</span>
                </td>
                <td>{{ $order->hours }} giờ</td>
                <td>
                    <span style="color: #10b981; font-weight: 700;">{{ number_format($order->amount, 0, ',', '.') }}đ</span>
                </td>
                <td>
                    @if($order->status === 'pending')
                        <span class="badge badge-pending">Chờ xử lý</span>
                    @elseif($order->status === 'paid')
                        <span class="badge badge-paid">Đã thanh toán</span>
                    @elseif($order->status === 'completed')
                        <span class="badge badge-completed">Hoàn thành</span>
                    @else
                        <span class="badge badge-cancelled">{{ $order->status }}</span>
                    @endif
                </td>
                <td>
                    @if($order->account)
                        <div>
                            <span style="color: #3b82f6; cursor: pointer;" title="{{ $order->account->username }}">
                                🔑 <strong>{{ $order->account->username }}</strong>
                            </span>
                        </div>
                        <div style="font-size: 13px; color: var(--text-muted); font-family: monospace;">
                            {{ $order->assigned_password ?? $order->account->password }}
                        </div>
                    @else
                        <span style="color: var(--text-dimmed);">—</span>
                    @endif
                </td>
                <td style="white-space: nowrap;">{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                <td>
                    @if($order->ip_address)
                        <span style="font-size: 12px; font-family: monospace; color: var(--text-muted);">{{ $order->ip_address }}</span>
                    @else
                        <span style="color: var(--text-dimmed);">—</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                        <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <select name="status" class="form-select" style="width: 130px; font-size: 12px; padding: 6px 8px;" onchange="this.form.submit()">
                                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                                <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                            </select>
                        </form>
                        @if($order->account && in_array($order->status, ['paid', 'completed']))
                        <button type="button" onclick="openReissueModal({{ $order->id }}, '{{ $order->tracking_code }}', '{{ $order->account->username }}', '{{ $order->account->password }}')" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border: none; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer; white-space: nowrap;" title="Cấp lại mật khẩu">
                            🔄 Cấp MK
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; color: #64748b; padding: 40px;">Không tìm thấy đơn hàng nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($orders->hasPages())
<div class="pagination">{{ $orders->links() }}</div>
@endif

{{-- Reissue Password Modal --}}
<div id="reissue-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background: var(--card-bg, #1e293b); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px; max-width: 440px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #f59e0b;">🔄 Cấp lại mật khẩu</h3>
            <button onclick="closeReissueModal()" style="background: none; border: none; color: rgba(255,255,255,0.5); font-size: 1.3rem; cursor: pointer;">&times;</button>
        </div>
        <div style="background: rgba(255,255,255,0.05); border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;">
            <div style="font-size: 0.78rem; color: rgba(255,255,255,0.4); margin-bottom: 4px;">Đơn hàng</div>
            <div style="font-weight: 700; color: #fff;" id="reissue-order-code"></div>
            <div style="font-size: 0.85rem; color: #3b82f6; margin-top: 4px;" id="reissue-account-name"></div>
        </div>
        <form id="reissue-form" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.82rem; color: rgba(255,255,255,0.6); font-weight: 600; margin-bottom: 6px;">Mật khẩu mới <span style="color: #ef4444;">*</span></label>
                <input type="text" name="new_password" id="reissue-password" required class="form-input" style="width: 100%; font-family: monospace; font-size: 0.95rem;" placeholder="Nhập mật khẩu mới...">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.82rem; color: rgba(255,255,255,0.6); font-weight: 600; margin-bottom: 6px;">Gia hạn thêm (giờ) <span style="color: rgba(255,255,255,0.3); font-weight: 400;">— tuỳ chọn</span></label>
                <input type="number" name="extend_hours" class="form-input" style="width: 100%;" min="0" value="0" placeholder="0 = không gia hạn">
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.35); margin-top: 4px;">Nếu đơn đã hết hạn, thời gian tính từ bây giờ + số giờ nhập.</div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeReissueModal()" style="flex: 1; padding: 12px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: rgba(255,255,255,0.6); font-weight: 600; cursor: pointer; font-size: 0.88rem;">Huỷ</button>
                <button type="submit" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #f59e0b, #d97706); border: none; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer; font-size: 0.88rem; box-shadow: 0 4px 12px rgba(245,158,11,0.3);">✓ Cấp lại</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReissueModal(orderId, orderCode, accountName, currentPassword) {
    document.getElementById('reissue-order-code').textContent = orderCode;
    document.getElementById('reissue-account-name').textContent = '🔑 ' + accountName;
    document.getElementById('reissue-password').value = currentPassword;
    document.getElementById('reissue-form').action = '/admin/orders/' + orderId + '/reissue-password';
    var modal = document.getElementById('reissue-modal');
    modal.style.display = 'flex';
    setTimeout(function() { document.getElementById('reissue-password').select(); }, 100);
}
function closeReissueModal() {
    document.getElementById('reissue-modal').style.display = 'none';
}
document.getElementById('reissue-modal').addEventListener('click', function(e) {
    if (e.target === this) closeReissueModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeReissueModal();
});
</script>
@endsection
