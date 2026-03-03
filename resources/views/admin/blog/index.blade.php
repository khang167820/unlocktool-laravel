@extends('admin.layouts.app')
@section('title', 'Quản Lý Blog')
@section('page-title', 'Quản Lý Blog')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon blue">📝</div>
        <div class="stat-info">
            <div class="stat-label">Tổng bài viết</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
            <div class="stat-label">Đã xuất bản</div>
            <div class="stat-value" style="color: #16a34a;">{{ $stats['published'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📋</div>
        <div class="stat-info">
            <div class="stat-label">Bản nháp</div>
            <div class="stat-value" style="color: #f97316;">{{ $stats['draft'] }}</div>
        </div>
    </div>
</div>

<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.blog.create') }}" class="btn btn-primary">✍️ Tạo bài mới</a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tiêu đề</th>
                <th>Danh mục</th>
                <th>Trạng thái</th>
                <th>Lượt xem</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($posts as $post)
            <tr>
                <td>#{{ $post->id }}</td>
                <td style="max-width: 300px;">
                    <strong>{{ Str::limit($post->title, 60) }}</strong>
                    <div style="font-size: 11px; color: var(--text-dimmed);">/blog/{{ $post->slug }}</div>
                </td>
                <td>{{ $post->category ?? '—' }}</td>
                <td>
                    <span class="badge badge-{{ $post->status }}">{{ $post->status === 'published' ? 'Đã xuất bản' : 'Nháp' }}</span>
                </td>
                <td>{{ number_format($post->views ?? 0) }}</td>
                <td>{{ \Carbon\Carbon::parse($post->created_at)->format('d/m/Y') }}</td>
                <td>
                    <div style="display: flex; gap: 4px;">
                        <a href="{{ route('admin.blog.edit', $post->id) }}" class="btn btn-sm btn-primary">✏️</a>
                        <form action="{{ route('admin.blog.toggle', $post->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $post->status === 'published' ? 'btn-secondary' : 'btn-success' }}">
                                {{ $post->status === 'published' ? '📋' : '✅' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.blog.delete', $post->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Xóa bài viết này?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">
                    Chưa có bài viết nào. <a href="{{ route('admin.blog.create') }}" style="color: #3b82f6;">Tạo bài viết đầu tiên →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($posts->hasPages())
<div class="pagination">{{ $posts->links() }}</div>
@endif
@endsection
