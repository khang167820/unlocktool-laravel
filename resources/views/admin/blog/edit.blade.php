@extends('admin.layouts.app')
@section('title', $post ? 'Edit Post' : 'New Post')
@section('page-title', $post ? 'Edit Post' : 'New Post')

@section('content')
<form action="{{ route('admin.blog.save', $post->id ?? null) }}" method="POST">
    @csrf
    
    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 24px;">
        <!-- Main Content -->
        <div>
            <div class="admin-card">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-input" value="{{ old('title', $post->title ?? '') }}" required
                           oninput="if(!document.querySelector('[name=slug]').dataset.edited) document.querySelector('[name=slug]').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')">
                </div>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-input" value="{{ old('slug', $post->slug ?? '') }}" required
                           onfocus="this.dataset.edited='1'">
                </div>
                <div class="form-group">
                    <label class="form-label">Excerpt</label>
                    <textarea name="excerpt" class="form-input" rows="3" placeholder="Short description...">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Content (HTML)</label>
                    <textarea name="content" class="form-input form-textarea" rows="20" required style="min-height: 400px; font-family: 'Courier New', monospace; font-size: 13px;">{{ old('content', $post->content ?? '') }}</textarea>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div>
            <!-- Publish Box -->
            <div class="admin-card">
                <div class="admin-card-title">📋 Publish</div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" {{ old('status', $post->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $post->status ?? '') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-input" value="{{ old('category', $post->category ?? 'Hướng Dẫn') }}" placeholder="e.g. Hướng Dẫn">
                </div>
                <div class="form-group">
                    <label class="form-label">Featured Image URL</label>
                    <input type="text" name="image" class="form-input" value="{{ old('image', $post->image ?? '') }}" placeholder="/images/post.jpg">
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_cornerstone" value="1" {{ old('is_cornerstone', $post->is_cornerstone ?? 0) ? 'checked' : '' }}>
                        <span class="form-label" style="margin: 0;">⭐ Cornerstone Content</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    {{ $post ? '💾 Update Post' : '✍️ Create Post' }}
                </button>
            </div>
            
            <!-- SEO Box -->
            <div class="admin-card">
                <div class="admin-card-title">🔍 SEO Settings</div>
                <div class="form-group">
                    <label class="form-label">Meta Title</label>
                    <input type="text" name="meta_title" class="form-input" value="{{ old('meta_title', $post->meta_title ?? '') }}" placeholder="SEO title...">
                    <div style="font-size: 11px; color: var(--text-dimmed); margin-top: 4px;">Recommended: 50-60 characters</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_description" class="form-input" rows="3" placeholder="SEO description...">{{ old('meta_description', $post->meta_description ?? '') }}</textarea>
                    <div style="font-size: 11px; color: var(--text-dimmed); margin-top: 4px;">Recommended: 120-160 characters</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Meta Keywords</label>
                    <input type="text" name="meta_keywords" class="form-input" value="{{ old('meta_keywords', $post->meta_keywords ?? '') }}" placeholder="keyword1, keyword2...">
                </div>
                <div class="form-group">
                    <label class="form-label">Focus Keyword</label>
                    <input type="text" name="focus_keyword" class="form-input" value="{{ old('focus_keyword', $post->focus_keyword ?? '') }}" placeholder="Primary keyword">
                </div>
                <div class="form-group">
                    <label class="form-label">Robots Meta</label>
                    <select name="robots_meta" class="form-select">
                        <option value="index, follow" {{ old('robots_meta', $post->robots_meta ?? 'index, follow') === 'index, follow' ? 'selected' : '' }}>index, follow</option>
                        <option value="noindex, follow" {{ old('robots_meta', $post->robots_meta ?? '') === 'noindex, follow' ? 'selected' : '' }}>noindex, follow</option>
                        <option value="index, nofollow" {{ old('robots_meta', $post->robots_meta ?? '') === 'index, nofollow' ? 'selected' : '' }}>index, nofollow</option>
                        <option value="noindex, nofollow" {{ old('robots_meta', $post->robots_meta ?? '') === 'noindex, nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
