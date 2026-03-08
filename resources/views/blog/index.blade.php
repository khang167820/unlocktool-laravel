@extends('layouts.app')

@section('title', 'Blog - Tin tức & Hướng dẫn UnlockTool')

@section('content')
<style>
/* ===============================================
   PREMIUM BLOG PAGE STYLES
   =============================================== */

/* Force no horizontal overflow - applied directly here to bypass any cache */
html, body {
    max-width: 100vw !important;
    overflow-x: hidden !important;
}

/* Prevent horizontal overflow on mobile */
.blog-hero,
.blog-container {
    max-width: 100vw;
    overflow-x: hidden;
    box-sizing: border-box;
}

/* Fix CSS Grid overflow: grid items default to min-width: auto, 
   which prevents them from shrinking below content width */
.blog-layout > * {
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
}

/* Hero Section */
.blog-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #020617 100%);
    padding: 80px 0 60px;
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.blog-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 80%, rgba(249, 115, 22, 0.15) 0%, transparent 40%),
        radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 40%);
    pointer-events: none;
}

.blog-hero-content {
    position: relative;
    z-index: 1;
    max-width: 700px;
    margin: 0 auto;
    padding: 0 20px;
}

.blog-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(249, 115, 22, 0.2);
    border: 1px solid rgba(249, 115, 22, 0.3);
    color: #fb923c;
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 20px;
    backdrop-filter: blur(10px);
}

.blog-hero h1 {
    font-size: 2.8rem;
    font-weight: 800;
    margin-bottom: 16px;
    line-height: 1.2;
}

.blog-hero h1 span {
    background: linear-gradient(135deg, #f97316 0%, #fb923c 50%, #fbbf24 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.blog-hero p {
    font-size: 1.1rem;
    opacity: 0.85;
    margin: 0 0 28px;
    line-height: 1.6;
}

/* Search Box */
.blog-search {
    max-width: 520px;
    margin: 0 auto;
    display: flex;
    gap: 12px;
    background: rgba(255, 255, 255, 0.1);
    padding: 8px;
    border-radius: 16px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.blog-search input {
    flex: 1;
    padding: 14px 20px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    background: #fff;
    outline: none;
}

.blog-search input:focus {
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
}

.blog-search button {
    padding: 14px 28px;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.blog-search button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(249, 115, 22, 0.4);
}

/* Container */
.blog-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 50px 20px;
}

.blog-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 40px;
    max-width: 100%;
    overflow: hidden;
}

/* Category Heading */
.category-heading {
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
}

.category-heading h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.category-heading h2 span {
    color: #f97316;
}

/* Blog Grid */
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 28px;
}

/* Blog Card */
.blog-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    max-width: 100%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.4s ease;
    text-decoration: none;
    border: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
}

.blog-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    border-color: #e5e7eb;
}

.blog-card-img {
    height: 180px;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 56px;
    position: relative;
    overflow: hidden;
}

.blog-card-img::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
}

.blog-card-img span {
    position: relative;
    z-index: 1;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
    transition: transform 0.3s ease;
}

.blog-card:hover .blog-card-img span {
    transform: scale(1.15) rotate(-5deg);
}

/* Blog card with real image */
.blog-card-img.has-image {
    background: #1e293b;
    padding: 0;
}

.blog-card-img.has-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.blog-card:hover .blog-card-img.has-image img {
    transform: scale(1.08);
}

.blog-card-body {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.blog-card-category {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
    width: fit-content;
}

/* Category Colors */
.blog-card-category.cat-huong-dan { background: #dbeafe; color: #1d4ed8; }
.blog-card-category.cat-review { background: #fef3c7; color: #d97706; }
.blog-card-category.cat-top-list { background: #f3e8ff; color: #9333ea; }
.blog-card-category.cat-so-sanh { background: #fce7f3; color: #db2777; }
.blog-card-category.cat-default { background: #f1f5f9; color: #475569; }

.blog-card-title {
    font-size: 17px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.5;
    margin-bottom: 0;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.2s ease;
}

.blog-card:hover .blog-card-title {
    color: #f97316;
}

.blog-card-meta {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: #9ca3af;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
}

.blog-card-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 40px;
    background: #fff;
    border-radius: 20px;
    border: 2px dashed #e5e7eb;
}

.empty-state-icon {
    font-size: 72px;
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.empty-state h3 {
    color: #1f2937;
    margin-bottom: 8px;
    font-size: 1.25rem;
}

.empty-state p {
    color: #6b7280;
}

/* Sidebar */
.blog-sidebar-box {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 24px;
    border: 1px solid #f1f5f9;
    max-width: 100%;
    overflow: hidden;
}

.blog-sidebar-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 3px solid #f97316;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    transition: all 0.2s ease;
}

.category-list li:not(:last-child) {
    border-bottom: 1px solid #f3f4f6;
}

.category-list a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-decoration: none;
    color: #4b5563;
    font-size: 14px;
    padding: 14px 0;
    transition: all 0.2s ease;
}

.category-list a:hover {
    color: #f97316;
    padding-left: 8px;
}

.category-count {
    background: #f3f4f6;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    transition: all 0.2s ease;
}

.category-list a:hover .category-count {
    background: #fff7ed;
    color: #f97316;
}

/* CTA Box */
.cta-box {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #fff;
    border: none;
    position: relative;
    overflow: hidden;
}

.cta-box::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
}

.cta-box-content {
    position: relative;
    z-index: 1;
}

.cta-box h3 {
    font-size: 1.15rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cta-box p {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 18px;
    line-height: 1.5;
}

.cta-btn {
    display: block;
    background: #fff;
    color: #ea580c;
    padding: 14px;
    text-align: center;
    border-radius: 12px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
}

.cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

/* Pagination */
.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 50px;
    max-width: 100%;
    overflow: hidden;
}

/* Fix Laravel Tailwind Pagination */
.pagination-wrapper nav {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
}

.pagination-wrapper nav > div:first-child {
    font-size: 14px;
    color: #6b7280;
}

.pagination-wrapper nav > div:last-child {
    display: flex;
    align-items: center;
    gap: 4px;
}

.pagination-wrapper nav span,
.pagination-wrapper nav a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.pagination-wrapper nav a {
    background: #fff;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.pagination-wrapper nav a:hover {
    background: #f97316;
    color: #fff;
    border-color: #f97316;
}

.pagination-wrapper nav span[aria-current="page"] span {
    background: #f97316;
    color: #fff;
    border-radius: 10px;
}

/* Fix SVG arrow size - CRITICAL */
.pagination-wrapper svg {
    width: 16px !important;
    height: 16px !important;
}

/* ===============================================
   DARK MODE
   =============================================== */
[data-theme="dark"] .blog-hero {
    background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
}

[data-theme="dark"] .blog-container {
    background: transparent;
}

[data-theme="dark"] .blog-search input {
    background: #1e293b;
    color: #f1f5f9;
}

[data-theme="dark"] .category-heading {
    border-color: #334155;
}

[data-theme="dark"] .category-heading h2 {
    color: #f1f5f9;
}

[data-theme="dark"] .blog-card {
    background: #1e293b;
    border-color: #334155;
}

[data-theme="dark"] .blog-card:hover {
    border-color: #475569;
}

[data-theme="dark"] .blog-card-title {
    color: #f1f5f9;
}

[data-theme="dark"] .blog-card-meta {
    color: #64748b;
    border-color: #334155;
}

[data-theme="dark"] .empty-state {
    background: #1e293b;
    border-color: #334155;
}

[data-theme="dark"] .empty-state h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .empty-state p {
    color: #94a3b8;
}

[data-theme="dark"] .blog-sidebar-box {
    background: #1e293b;
    border-color: #334155;
}

[data-theme="dark"] .blog-sidebar-title {
    color: #f1f5f9;
}

[data-theme="dark"] .category-list li {
    border-color: #334155;
}

[data-theme="dark"] .category-list a {
    color: #94a3b8;
}

[data-theme="dark"] .category-count {
    background: #334155;
    color: #94a3b8;
}

/* Featured Posts */
.featured-section {
    margin-bottom: 36px;
}
.featured-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.featured-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.featured-card {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    text-decoration: none;
    transition: all 0.3s ease;
    align-items: flex-start;
}
.featured-card:hover {
    border-color: #f97316;
    box-shadow: 0 4px 16px rgba(249,115,22,0.12);
    transform: translateY(-2px);
}
.featured-rank {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 800;
    flex-shrink: 0;
}
.featured-rank.r1 { background: linear-gradient(135deg, #f59e0b, #d97706); }
.featured-rank.r2 { background: linear-gradient(135deg, #6366f1, #4f46e5); }
.featured-rank.r3 { background: linear-gradient(135deg, #10b981, #059669); }
.featured-info { flex: 1; min-width: 0; }
.featured-info h4 {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.4;
    margin: 0 0 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.featured-card:hover .featured-info h4 { color: #f97316; }
.featured-meta {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    gap: 10px;
}

[data-theme="dark"] .featured-title { color: #f1f5f9; }
[data-theme="dark"] .featured-card { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .featured-info h4 { color: #f1f5f9; }

/* ===============================================
   RESPONSIVE
   =============================================== */
@media (max-width: 1024px) {
    .blog-layout {
        grid-template-columns: 1fr;
    }
    
    .blog-sidebar-box {
        max-width: 100%;
    }
}

@media (max-width: 768px) {
    .blog-hero {
        padding: 50px 0 40px;
    }
    
    .blog-hero h1 {
        font-size: 1.8rem;
    }
    
    .blog-hero p {
        font-size: 0.95rem;
    }
    
    .blog-search {
        flex-direction: column;
    }
    
    .blog-search button {
        justify-content: center;
    }
    
    .blog-container {
        padding: 30px 16px;
    }
    
    .blog-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .blog-card-body {
        padding: 18px;
    }
    
    .featured-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Hero -->
<section class="blog-hero">
    <div class="blog-hero-content">
        <div class="blog-hero-badge">
            <span>📚</span> Blog & Hướng dẫn UnlockTool
        </div>
        <h1>Tin tức & <span>Kiến thức</span> hữu ích</h1>
        <p>Hướng dẫn sử dụng UnlockTool, mẹo xóa FRP, review tool và nhiều kiến thức chuyên ngành khác</p>
        
        <form action="{{ route('blog.index') }}" method="GET" class="blog-search">
            <input type="text" name="q" placeholder="🔍 Tìm kiếm bài viết..." value="{{ request('q') }}">
            <button type="submit">Tìm kiếm</button>
        </form>
    </div>
</section>

<!-- Content -->
<div class="blog-container">
    <div class="blog-layout">
        <!-- Posts Grid -->
        <div>
            {{-- Featured Posts - only on page 1, no category filter --}}
            @if(!isset($category) && !request('page') && isset($featuredPosts) && $featuredPosts->isNotEmpty())
            <div class="featured-section">
                <h2 class="featured-title">🔥 Bài viết nổi bật</h2>
                <div class="featured-grid">
                    @foreach($featuredPosts as $idx => $feat)
                    <a href="{{ route('blog.show', $feat->slug) }}" class="featured-card">
                        <span class="featured-rank r{{ $idx + 1 }}">{{ $idx + 1 }}</span>
                        <div class="featured-info">
                            <h4>{{ $feat->title }}</h4>
                            <div class="featured-meta">
                                <span>👁 {{ number_format($feat->views) }}</span>
                                <span>{{ $feat->category }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
            
            @if(isset($category))
                <div class="category-heading">
                    <h2>Danh mục: <span>{{ $category }}</span></h2>
                </div>
            @endif
            
            @if($posts->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>Không tìm thấy bài viết</h3>
                    <p>Thử tìm kiếm với từ khóa khác hoặc xem tất cả bài viết</p>
                </div>
            @else
                <div class="blog-grid">
                    @foreach($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="blog-card">
                        @php
                            $icons = ['📱', '🔓', '💻', '🔧', '📦', '⚙️', '🛠️', '📡'];
                            $icon = $icons[crc32($post->slug) % count($icons)];
                            
                            // Category class
                            $catLower = strtolower($post->category);
                            $catClass = 'cat-default';
                            if (str_contains($catLower, 'hướng dẫn')) $catClass = 'cat-huong-dan';
                            elseif (str_contains($catLower, 'review')) $catClass = 'cat-review';
                            elseif (str_contains($catLower, 'top')) $catClass = 'cat-top-list';
                            elseif (str_contains($catLower, 'so sánh')) $catClass = 'cat-so-sanh';
                            
                            // Check for image - priority: thumbnail_image from DB > slug-based image file
                            $hasImage = false;
                            $imagePath = '';
                            
                            if (!empty($post->thumbnail_image)) {
                                $hasImage = true;
                                $imagePath = $post->thumbnail_image;
                            } else {
                                // Try slug-based image
                                $slugImage = '/images/blog/' . $post->slug . '.png';
                                if (file_exists(public_path('images/blog/' . $post->slug . '.png'))) {
                                    $hasImage = true;
                                    $imagePath = $slugImage;
                                }
                            }
                        @endphp
                        
                        @if($hasImage)
                        <div class="blog-card-img has-image">
                            <img src="{{ $imagePath }}" alt="{{ $post->title }}" loading="lazy">
                        </div>
                        @else
                        <div class="blog-card-img">
                            <span>{{ $icon }}</span>
                        </div>
                        @endif
                        <div class="blog-card-body">
                            <span class="blog-card-category {{ $catClass }}">{{ $post->category }}</span>
                            <h3 class="blog-card-title">{{ $post->title }}</h3>
                            <div class="blog-card-meta">
                                <span>👁 {{ number_format($post->views) }}</span>
                                <span>📅 {{ \Carbon\Carbon::parse($post->created_at)->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                
                <div class="pagination-wrapper">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
        
        <!-- Sidebar -->
        <aside>
            <!-- Categories -->
            <div class="blog-sidebar-box">
                <h3 class="blog-sidebar-title">📁 Danh mục</h3>
                <ul class="category-list">
                    @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('blog.category', $cat->category) }}">
                            {{ $cat->category }}
                            <span class="category-count">{{ $cat->count }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            
            <!-- CTA -->
            <div class="blog-sidebar-box cta-box">
                <div class="cta-box-content">
                    <h3>🔓 Thuê Tool GSM</h3>
                    <p>Giá chỉ từ 10.000đ - Nhận tài khoản tự động 24/7!</p>
                    <a href="/" class="cta-btn">
                        Xem dịch vụ →
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
