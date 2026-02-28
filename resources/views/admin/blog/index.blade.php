@extends('admin.layouts.app')
@section('title', 'Blog Management')
@section('page-title', 'Blog Management')

@section('content')
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon blue">📝</div>
        <div class="stat-info">
            <div class="stat-label">Total Posts</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
            <div class="stat-label">Published</div>
            <div class="stat-value" style="color: #16a34a;">{{ $stats['published'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📋</div>
        <div class="stat-info">
            <div class="stat-label">Draft</div>
            <div class="stat-value" style="color: #f97316;">{{ $stats['draft'] }}</div>
        </div>
    </div>
</div>

<div style="margin-bottom: 20px;">
    <a href="{{ route('admin.blog.create') }}" class="btn btn-primary">✍️ New Post</a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Views</th>
                <th>Created</th>
                <th>Actions</th>
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
                    <span class="badge badge-{{ $post->status }}">{{ $post->status }}</span>
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
                        <form action="{{ route('admin.blog.delete', $post->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this post?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #64748b; padding: 40px;">
                    No blog posts yet. <a href="{{ route('admin.blog.create') }}" style="color: #3b82f6;">Create your first post →</a>
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
