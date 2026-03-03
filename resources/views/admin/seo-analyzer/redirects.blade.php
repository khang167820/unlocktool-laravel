@extends('admin.layouts.app')
@section('title', '301 Redirect Manager')

@section('content')
<style>
.redirect-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.redirect-stat { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 16px; }
.redirect-stat .icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.redirect-stat .icon.blue { background: #dbeafe; }
.redirect-stat .icon.green { background: #dcfce7; }
.redirect-stat .icon.orange { background: #ffedd5; }
.redirect-stat .value { font-size: 26px; font-weight: 800; color: #1e293b; }
.redirect-stat .label { font-size: 12px; color: #64748b; margin-top: 2px; }

.redirect-form { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 24px; }
.redirect-form .form-row { display: grid; grid-template-columns: 1fr 1fr 140px 120px; gap: 12px; align-items: end; }
.redirect-form label { display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.redirect-form input, .redirect-form select { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: #fff; transition: all 0.2s; }
.redirect-form input:focus, .redirect-form select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.redirect-form input::placeholder { color: #94a3b8; }
.redirect-form .hint { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.redirect-form .btn-add { padding: 10px 20px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.redirect-form .btn-add:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }

.redirect-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.redirect-table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-weight: 600; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
.redirect-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.redirect-table tr:hover td { background: #f8fafc; }
.redirect-table code { background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 12px; color: #475569; font-family: 'JetBrains Mono', monospace; }
.redirect-table .status-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.redirect-table .status-301 { background: #dcfce7; color: #16a34a; }
.redirect-table .status-302 { background: #fef3c7; color: #d97706; }
.redirect-table .hits-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #64748b; }
.redirect-table .hits-badge strong { color: #1e293b; }
.redirect-table .btn-delete { padding: 6px 12px; background: none; border: 1px solid #fecaca; color: #ef4444; border-radius: 8px; font-size: 12px; cursor: pointer; transition: all 0.2s; }
.redirect-table .btn-delete:hover { background: #fef2f2; border-color: #ef4444; }

.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state .icon { font-size: 48px; margin-bottom: 12px; }
.empty-state h3 { color: #64748b; margin-bottom: 8px; }

.tip-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-top: 24px; }
.tip-card h4 { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 12px; }
.tip-card ul { margin: 0; padding-left: 0; list-style: none; }
.tip-card li { padding: 6px 0; font-size: 13px; color: #475569; display: flex; align-items: baseline; gap: 8px; }
.tip-card li::before { content: '•'; color: #3b82f6; font-weight: 700; }

.alert-msg { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; animation: slideIn 0.3s ease; }
.alert-msg.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
.alert-msg.error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
@keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 768px) {
    .redirect-form .form-row { grid-template-columns: 1fr; }
    .redirect-stats { grid-template-columns: 1fr; }
}
</style>

<div style="padding: 24px;">
    <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;">🔀 301 Redirect Manager</h2>
    <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Quản lý chuyển hướng URL — bảo toàn SEO khi đổi đường dẫn</p>

    @if(session('success'))
    <div class="alert-msg success">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert-msg error">❌ {{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="redirect-stats">
        <div class="redirect-stat">
            <div class="icon blue">🔀</div>
            <div><div class="value">{{ count($redirects) }}</div><div class="label">Tổng Redirect</div></div>
        </div>
        <div class="redirect-stat">
            <div class="icon green">✅</div>
            <div><div class="value">{{ collect($redirects)->where('status_code', 301)->count() }}</div><div class="label">301 Permanent</div></div>
        </div>
        <div class="redirect-stat">
            <div class="icon orange">⏳</div>
            <div><div class="value">{{ collect($redirects)->sum('hits') }}</div><div class="label">Tổng lượt truy cập</div></div>
        </div>
    </div>

    {{-- Add Redirect Form --}}
    <div class="redirect-form">
        <div style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 16px;">➕ Thêm Redirect Mới</div>
        <form action="{{ route('admin.seo.redirects.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div>
                    <label>URL cũ (từ)</label>
                    <input type="text" name="from_url" placeholder="/duong-dan-cu" required>
                    <div class="hint">VD: /tin-tuc/bai-viet-cu</div>
                </div>
                <div>
                    <label>URL mới (đến)</label>
                    <input type="text" name="to_url" placeholder="/duong-dan-moi" required>
                    <div class="hint">VD: /blog/bai-viet-moi</div>
                </div>
                <div>
                    <label>Loại</label>
                    <select name="status_code">
                        <option value="301">301 Permanent</option>
                        <option value="302">302 Temporary</option>
                    </select>
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-add">➕ Thêm</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Redirect List --}}
    <div style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
            <div style="font-size: 14px; font-weight: 700; color: #1e293b;">📋 Danh sách Redirect</div>
            <div style="font-size: 12px; color: #94a3b8;">{{ count($redirects) }} rules</div>
        </div>
        
        @if(count($redirects) > 0)
        <table class="redirect-table">
            <thead>
                <tr>
                    <th>URL cũ</th>
                    <th>→</th>
                    <th>URL mới</th>
                    <th>Loại</th>
                    <th>Lượt truy cập</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($redirects as $redirect)
                <tr>
                    <td><code>{{ $redirect->from_url }}</code></td>
                    <td style="color: #94a3b8; font-size: 16px;">→</td>
                    <td><code>{{ $redirect->to_url }}</code></td>
                    <td>
                        <span class="status-badge status-{{ $redirect->status_code }}">
                            {{ $redirect->status_code }}
                        </span>
                    </td>
                    <td>
                        <span class="hits-badge">
                            📊 <strong>{{ number_format($redirect->hits) }}</strong> hits
                        </span>
                    </td>
                    <td style="font-size: 12px; color: #94a3b8;">
                        {{ \Carbon\Carbon::parse($redirect->created_at)->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        <form action="{{ route('admin.seo.redirects.delete', $redirect->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Xóa redirect này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete">🗑️ Xóa</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <div class="icon">🔀</div>
            <h3>Chưa có redirect nào</h3>
            <p>Thêm redirect đầu tiên bằng form ở trên khi cần chuyển hướng URL.</p>
        </div>
        @endif
    </div>

    {{-- Tips --}}
    <div class="tip-card">
        <h4>💡 Khi nào cần dùng Redirect?</h4>
        <ul>
            <li><strong>301 Permanent:</strong> URL đã chuyển vĩnh viễn → Google chuyển toàn bộ ranking sang URL mới</li>
            <li><strong>302 Temporary:</strong> Chuyển hướng tạm thời (bảo trì, A/B test) → Google giữ ranking ở URL cũ</li>
            <li><strong>Đổi slug bài viết:</strong> Khi thay đổi URL bài blog, thêm redirect từ URL cũ → mới</li>
            <li><strong>Xóa trang:</strong> Redirect trang đã xóa về trang tương tự để không mất traffic</li>
            <li><strong>Google Search Console:</strong> Nếu thấy 404 errors, thêm redirect để fix</li>
        </ul>
    </div>
</div>
@endsection
