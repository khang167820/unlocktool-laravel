@extends('layouts.app')

@php
    $blogPage = max(1, (int) request('page', 1));
    $blogCategory = request()->route('category');
    $blogSearch = trim((string) request('search', ''));

    if ($blogSearch !== '') {
        $blogTitle = 'Kết quả tìm kiếm bài viết | UnlockTool.us';
        $blogDescription = 'Kết quả tìm kiếm trong thư viện hướng dẫn UnlockTool và sửa chữa điện thoại.';
        $blogCanonical = route('blog.index');
        $blogRobots = 'noindex, follow';
    } elseif ($blogCategory) {
        $blogTitle = "Bài Viết {$blogCategory} | UnlockTool.us";
        $blogDescription = "Hướng dẫn và kiến thức mới nhất về {$blogCategory} dành cho kỹ thuật viên điện thoại.";
        $blogCanonical = url()->current();
        $blogRobots = 'index, follow, max-image-preview:large';
    } else {
        $blogTitle = 'Blog UnlockTool – Hướng Dẫn FRP, Flash Và Mở Khóa';
        $blogDescription = 'Hướng dẫn sử dụng UnlockTool, xóa FRP, flash firmware và xử lý lỗi điện thoại dành cho kỹ thuật viên.';
        $blogCanonical = route('blog.index');
        $blogRobots = 'index, follow, max-image-preview:large';
    }

    if ($blogPage > 1 && $blogSearch === '') {
        $blogTitle .= " – Trang {$blogPage}";
        $blogCanonical = url()->current() . '?page=' . $blogPage;
    }
@endphp

@section('title', $blogTitle)
@section('meta_description', $blogDescription)
@section('canonical_url', $blogCanonical)
@section('robots_meta', $blogRobots)

@section('head')
@if($posts->previousPageUrl())
<link rel="prev" href="{{ $posts->previousPageUrl() }}">
@endif
@if($posts->nextPageUrl())
<link rel="next" href="{{ $posts->nextPageUrl() }}">
@endif
@endsection

@section('content')
<style>
.blog-hero { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #8b5cf6 100%); padding: 50px 0; color: #fff; }
.blog-hero-title { font-size: 2rem; font-weight: 800; margin-bottom: 10px; }
.blog-hero-desc { font-size: 16px; opacity: 0.9; }
.blog-content { background: #f8fafc; padding: 40px 0 80px; }
.blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.blog-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: all 0.3s; }
.blog-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); }
.blog-card-img { height: 200px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 48px; }
.blog-card-body { padding: 24px; }
.blog-card-cat { display: inline-block; background: #eff6ff; color: #2563eb; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 12px; }
.blog-card-title { font-size: 1.1rem; font-weight: 700; color: #1f2937; margin-bottom: 8px; line-height: 1.4; }
.blog-card-title a { color: inherit; text-decoration: none; }
.blog-card-title a:hover { color: #3b82f6; }
.blog-card-excerpt { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 16px; }
.blog-card-meta { display: flex; gap: 16px; font-size: 12px; color: #94a3b8; }
.blog-categories { display: flex; gap: 8px; flex-wrap: wrap; max-width: 1200px; margin: 0 auto 24px; padding: 0 20px; }
.blog-cat-pill { padding: 8px 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; font-size: 13px; font-weight: 600; color: #374151; text-decoration: none; transition: all 0.2s; }
.blog-cat-pill:hover, .blog-cat-pill.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.blog-search { max-width: 1200px; margin: 0 auto 24px; padding: 0 20px; }
.blog-search-form { display: flex; background: #fff; border-radius: 12px; border: 2px solid #e5e7eb; overflow: hidden; max-width: 400px; }
.blog-search-input { flex: 1; padding: 10px 16px; border: none; font-size: 14px; outline: none; }
.blog-search-btn { padding: 10px 20px; background: #3b82f6; color: #fff; border: none; font-weight: 600; cursor: pointer; }
.blog-pagination { max-width: 1200px; margin: 24px auto 0; padding: 0 20px; display: flex; justify-content: center; }
@media (max-width: 768px) { .blog-grid { grid-template-columns: 1fr; } .blog-hero-title { font-size: 1.5rem; } }
</style>

<section class="blog-hero">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h1 class="blog-hero-title">📝 {{ $blogCategory ? "Bài viết {$blogCategory}" : 'Blog UnlockTool' }}</h1>
        <p class="blog-hero-desc">Hướng dẫn, kinh nghiệm và tin tức về UnlockTool dành cho kỹ thuật viên điện thoại</p>
    </div>
</section>

<section class="blog-content">
    <!-- Search -->
    <div class="blog-search">
        <form class="blog-search-form" action="{{ route('blog.index') }}" method="GET">
            <label for="blog-search-input" class="sr-only">Tìm bài viết</label>
            <input id="blog-search-input" type="search" name="search" class="blog-search-input" placeholder="Tìm bài viết..." value="{{ request('search') }}">
            <button type="submit" class="blog-search-btn">🔍</button>
        </form>
    </div>
    
    <!-- Categories -->
    @if($categories->isNotEmpty())
    <div class="blog-categories">
        <a href="{{ route('blog.index') }}" class="blog-cat-pill {{ !$blogCategory ? 'active' : '' }}">Tất cả</a>
        @foreach($categories as $cat)
        <a href="{{ route('blog.category', $cat->category) }}" class="blog-cat-pill {{ $blogCategory === $cat->category ? 'active' : '' }}">
            {{ $cat->category }} ({{ $cat->count }})
        </a>
        @endforeach
    </div>
    @endif
    
    <!-- Posts Grid -->
    <div class="blog-grid">
        @forelse($posts as $post)
        <article class="blog-card">
            <div class="blog-card-img">
                @if($post->image)
                    <img src="{{ $post->image }}" alt="{{ $post->title }}" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:cover;">
                @else
                    📄
                @endif
            </div>
            <div class="blog-card-body">
                <span class="blog-card-cat">{{ $post->category }}</span>
                <h2 class="blog-card-title">
                    <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                </h2>
                <p class="blog-card-excerpt">{{ Str::limit(strip_tags($post->excerpt ?: $post->content), 120) }}</p>
                <div class="blog-card-meta">
                    <span>📅 {{ \Carbon\Carbon::parse($post->created_at)->format('d/m/Y') }}</span>
                    <span>👁 {{ number_format($post->views) }} lượt xem</span>
                </div>
            </div>
        </article>
        @empty
        <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #64748b;">
            <div style="font-size: 48px; margin-bottom: 16px;">📝</div>
            <h3 style="color: #1f2937; margin-bottom: 8px;">Chưa có bài viết</h3>
            <p>Vui lòng quay lại sau để xem nội dung mới.</p>
        </div>
        @endforelse
    </div>
    
    @if($posts->hasPages())
    <div class="blog-pagination">{{ $posts->links() }}</div>
    @endif
</section>
@endsection
