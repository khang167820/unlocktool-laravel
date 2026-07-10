@extends('admin.layouts.app')

@section('title', 'Quản lý Gói thuê')
@section('page-title', 'Quản lý Gói thuê')

@section('content')
<!-- Form thêm gói -->
<div class="admin-card">
    <div class="admin-card-title">➕ Thêm gói thuê mới</div>
    <form action="{{ route('admin.prices.save') }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Thời lượng (giờ)*</label>
                <input type="number" name="hours" class="form-input" required min="1" placeholder="VD: 24">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Giá (VNĐ)*</label>
                <input type="number" name="price" class="form-input" required min="1000" placeholder="VD: 50000">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Giá gốc</label>
                <input type="number" name="original_price" class="form-input" placeholder="Để tạo giảm giá">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">% Giảm</label>
                <input type="number" name="discount_percent" class="form-input" placeholder="VD: 20">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Badge</label>
                <input type="text" name="promo_badge" class="form-input" placeholder="VD: HOT">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Hết hạn KM</label>
                <input type="datetime-local" name="promo_end" class="form-input">
            </div>
            <button type="submit" class="btn btn-success">+ Thêm</button>
        </div>
    </form>
</div>

<!-- Bảng gói thuê -->
<div class="admin-card">
    <div class="admin-card-title">💰 Danh sách gói Unlocktool</div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thời lượng</th>
                    <th>Giá</th>
                    <th>🌙 Giá đêm</th>
                    <th>Giá gốc</th>
                    <th>% Giảm</th>
                    <th>Badge</th>
                    <th>Hết hạn KM</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prices as $price)
                <tr>
                    <td>{{ $price->id }}</td>
                    <td>
                        <span style="font-weight: 600; color: #3b82f6;">{{ $price->hours }} giờ</span>
                        @if($price->hours >= 24)
                            <div style="font-size: 11px; color: #64748b;">({{ round($price->hours/24, 1) }} ngày)</div>
                        @endif
                    </td>
                    <td style="font-weight: 600; color: #10b981;">{{ number_format($price->price) }}đ</td>
                    <td>
                        @if(($price->night_price ?? null))
                            <div style="font-weight: 600; color: #f59e0b;">{{ number_format($price->night_price) }}đ</div>
                            <div style="font-size: 11px; color: #64748b;">{{ $price->night_start ?? '21:00' }} → {{ $price->night_end ?? '09:00' }}</div>
                            @php
                                $priceModel = new \App\Models\Price();
                                $priceModel->night_price = $price->night_price;
                                $priceModel->night_start = $price->night_start ?? '21:00';
                                $priceModel->night_end = $price->night_end ?? '09:00';
                                $isNight = $priceModel->isNightTime();
                            @endphp
                            <span class="badge {{ $isNight ? 'badge-active' : 'badge-pending' }}" style="font-size: 10px;">{{ $isNight ? '🌙 Đang áp dụng' : '☀️ Giá ngày' }}</span>
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    <td>
                        @if(($price->original_price ?? null))
                            <span style="text-decoration: line-through; color: #64748b;">{{ number_format($price->original_price) }}đ</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if(($price->discount_percent ?? null))
                            <span class="badge badge-active">-{{ $price->discount_percent }}%</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if(($price->promo_badge ?? null))
                            <span class="badge badge-pending">{{ $price->promo_badge }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size: 12px;">
                        @if(($price->promo_end ?? null))
                            {{ \Carbon\Carbon::parse($price->promo_end)->format('d/m/Y H:i') }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-sm btn-secondary" onclick="editPrice({{ json_encode($price) }})">Sửa</button>
                            
                            <form action="{{ route('admin.prices.delete', $price->id) }}" method="POST" style="margin:0;" 
                                  onsubmit="return confirm('Xác nhận xóa gói này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                        Chưa có gói nào
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal sửa gói -->
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
    <div style="background: var(--bg-secondary); padding:24px; border-radius:16px; width:500px; max-width:90%; border: 1px solid var(--border-color);">
        <h3 style="margin-bottom:16px; color: var(--text-primary);">Sửa gói thuê</h3>
        <form id="editForm" method="POST">
            @csrf
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label class="form-label">Thời lượng (giờ)*</label>
                    <input type="number" name="hours" id="edit_hours" class="form-input" required min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Giá (VNĐ)*</label>
                    <input type="number" name="price" id="edit_price" class="form-input" required min="1000">
                </div>
                <div class="form-group">
                    <label class="form-label">Giá gốc</label>
                    <input type="number" name="original_price" id="edit_original_price" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">% Giảm</label>
                    <input type="number" name="discount_percent" id="edit_discount_percent" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Badge</label>
                    <input type="text" name="promo_badge" id="edit_promo_badge" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Hết hạn KM</label>
                    <input type="datetime-local" name="promo_end" id="edit_promo_end" class="form-input">
                </div>
            </div>

            <!-- Night Discount Section -->
            <div style="border-top: 1px solid var(--border-color); margin-top: 16px; padding-top: 16px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: var(--text-primary);">
                        <input type="checkbox" id="edit_night_toggle" onchange="toggleNightFields()" style="width: 18px; height: 18px; accent-color: #f59e0b;">
                        🌙 Giảm giá đêm
                    </label>
                    <span id="nightStatusBadge" style="font-size: 11px; padding: 2px 8px; border-radius: 12px; background: #fef3c7; color: #92400e;"></span>
                </div>
                <div id="nightFields" style="display: none; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Giá đêm (VNĐ)</label>
                        <input type="number" name="night_price" id="edit_night_price" class="form-input" placeholder="VD: 8000" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bắt đầu</label>
                        <input type="time" name="night_start" id="edit_night_start" class="form-input" value="21:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kết thúc</label>
                        <input type="time" name="night_end" id="edit_night_end" class="form-input" value="09:00">
                    </div>
                </div>
            </div>
            
            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top: 16px;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPrice(price) {
    document.getElementById('editModal').style.display = 'flex';
    document.getElementById('editForm').action = '/admin/prices/save/' + price.id;
    document.getElementById('edit_hours').value = price.hours;
    document.getElementById('edit_price').value = price.price;
    document.getElementById('edit_original_price').value = price.original_price || '';
    document.getElementById('edit_discount_percent').value = price.discount_percent || '';
    document.getElementById('edit_promo_badge').value = price.promo_badge || '';
    
    if (price.promo_end) {
        const date = new Date(price.promo_end);
        document.getElementById('edit_promo_end').value = date.toISOString().slice(0, 16);
    } else {
        document.getElementById('edit_promo_end').value = '';
    }

    // Night discount fields
    var hasNight = price.night_price && price.night_price > 0;
    document.getElementById('edit_night_toggle').checked = hasNight;
    document.getElementById('edit_night_price').value = hasNight ? price.night_price : '';
    document.getElementById('edit_night_start').value = price.night_start || '21:00';
    document.getElementById('edit_night_end').value = price.night_end || '09:00';
    toggleNightFields();
}

function toggleNightFields() {
    var checked = document.getElementById('edit_night_toggle').checked;
    var fields = document.getElementById('nightFields');
    fields.style.display = checked ? 'grid' : 'none';
    if (!checked) {
        document.getElementById('edit_night_price').value = '';
    }
    // Update status badge
    var badge = document.getElementById('nightStatusBadge');
    if (checked) {
        var now = new Date();
        var h = now.getHours(), m = now.getMinutes();
        var currentMin = h * 60 + m;
        var startVal = document.getElementById('edit_night_start').value || '21:00';
        var endVal = document.getElementById('edit_night_end').value || '09:00';
        var sp = startVal.split(':'), ep = endVal.split(':');
        var startMin = parseInt(sp[0]) * 60 + parseInt(sp[1] || 0);
        var endMin = parseInt(ep[0]) * 60 + parseInt(ep[1] || 0);
        var isNight = startMin > endMin ? (currentMin >= startMin || currentMin < endMin) : (currentMin >= startMin && currentMin < endMin);
        badge.textContent = isNight ? '🌙 Đang giảm giá đêm' : '☀️ Chưa đến giờ';
        badge.style.background = isNight ? '#fef3c7' : '#ecfdf5';
        badge.style.color = isNight ? '#92400e' : '#065f46';
    } else {
        badge.textContent = 'Tắt';
        badge.style.background = '#f1f5f9';
        badge.style.color = '#64748b';
    }
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
@endsection
