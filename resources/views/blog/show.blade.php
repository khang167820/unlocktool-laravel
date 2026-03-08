@extends('layouts.app')
@section('og_type', 'article')

@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $post->meta_description ?: Str::limit(strip_tags($post->content), 160))
@section('og_image', $post->image ? url($post->image) : asset('images/og-default.png'))
@if(!empty($post->robots_meta) && $post->robots_meta !== 'index, follow')
@section('robots_meta', $post->robots_meta)
@endif

@section('schema')
{{-- Open Graph Article Time Tags --}}
<meta property="article:published_time" content="{{ \Carbon\Carbon::parse($post->created_at)->toIso8601String() }}">
<meta property="article:modified_time" content="{{ \Carbon\Carbon::parse($post->updated_at)->toIso8601String() }}">
<meta property="article:author" content="Thuetaikhoan.net">
<meta property="article:section" content="{{ $post->category }}">

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": "{{ e($post->meta_title ?: $post->title) }}",
    "description": "{{ e($post->meta_description ?: Str::limit(strip_tags($post->content), 160)) }}",
    "image": "{{ $post->image ? url($post->image) : asset('images/og-default.png') }}",
    "author": {
        "@@type": "Person",
        "name": "Admin ThueTaiKhoan",
        "url": "{{ url('/gioi-thieu') }}",
        "jobTitle": "Kỹ thuật viên GSM & Quản trị hệ thống",
        "worksFor": {
            "@@type": "Organization",
            "name": "Thuetaikhoan.net",
            "url": "{{ url('/') }}"
        }
    },
    "publisher": {
        "@@type": "Organization",
        "name": "thuetaikhoan.net",
        "logo": {
            "@@type": "ImageObject",
            "url": "{{ asset('assets/images/logo.webp') }}"
        }
    },
    "datePublished": "{{ \Carbon\Carbon::parse($post->created_at)->toIso8601String() }}",
    "dateModified": "{{ \Carbon\Carbon::parse($post->updated_at)->toIso8601String() }}",
    "mainEntityOfPage": {
        "@@type": "WebPage",
        "@@id": "{{ url()->current() }}"
    },
    "keywords": "{{ e($post->meta_keywords ?? '') }}"
}
</script>
@if($ratingCount > 0)
<script type="application/ld+json">
{
    "@@context": "https://schema.org/",
    "@@type": "Product",
    "name": "{{ e($post->meta_title ?: $post->title) }}",
    "description": "{{ e($post->meta_description ?: Str::limit(strip_tags($post->content), 160)) }}",
    "image": "{{ $post->image ? url($post->image) : asset('images/og-default.png') }}",
    "brand": {
        "@@type": "Brand",
        "name": "Thuetaikhoan.net"
    },
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "{{ $ratingAvg }}",
        "bestRating": "5",
        "worstRating": "1",
        "ratingCount": "{{ $ratingCount }}"
    },
    "offers": {
        "@@type": "Offer",
        "url": "{{ url()->current() }}",
        "priceCurrency": "VND",
        "price": "8000",
        "availability": "https://schema.org/InStock",
        "seller": {
            "@@type": "Organization",
            "name": "Thuetaikhoan.net"
        }
    }
}
</script>
@endif
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "Trang chủ",
            "item": "{{ url('/') }}"
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": "Blog",
            "item": "{{ route('blog.index') }}"
        },
        {
            "@@type": "ListItem",
            "position": 3,
            "name": "{{ e($post->category) }}",
            "item": "{{ route('blog.index', ['category' => $post->category]) }}"
        },
        {
            "@@type": "ListItem",
            "position": 4,
            "name": "{{ e($post->title) }}"
        }
    ]
}
</script>

@if(isset($faqSchema))
<!-- FAQ Schema for Rich Snippets -->
<script type="application/ld+json">
{!! $faqSchema !!}
</script>
@endif

@if(isset($howToSchema))
<!-- HowTo Schema for Rich Snippets -->
<script type="application/ld+json">
{!! $howToSchema !!}
</script>
@endif

@if(isset($videoSchema))
<!-- Video Schema for SERP Thumbnails -->
<script type="application/ld+json">
{!! $videoSchema !!}
</script>
@endif
@stop

@section('content')
<style>
.blog-post-hero {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #8b5cf6 100%);
    padding: 60px 0;
    color: #fff;
}
.blog-post-hero .container { max-width: 900px; }
.blog-breadcrumb { display: flex; gap: 8px; font-size: 14px; margin-bottom: 20px; opacity: 0.9; flex-wrap: wrap; }
.blog-breadcrumb a { color: #fff; text-decoration: none; }
.blog-post-category {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 16px;
}
.blog-post-title { font-size: 2rem; font-weight: 800; line-height: 1.3; margin-bottom: 20px; }
.blog-post-meta { display: flex; gap: 20px; flex-wrap: wrap; font-size: 14px; opacity: 0.9; }

.blog-post-content { background: #f8fafc; padding: 50px 0 80px; }
.blog-post-wrapper {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    box-sizing: border-box;
}
@media (max-width: 900px) {
    .blog-post-wrapper { grid-template-columns: 1fr; }
    .sidebar-video-box { max-width: 280px; margin: 0 auto; }
}

.blog-article {
    background: #fff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow-x: hidden;
    word-wrap: break-word;
    overflow-wrap: break-word;
    min-width: 0;
}
.blog-article h2 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1e40af;
    margin: 30px 0 15px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}
.blog-article h2:first-of-type { margin-top: 0; padding-top: 0; border-top: none; }
.blog-article h3 { font-size: 1.15rem; font-weight: 700; color: #1f2937; margin: 20px 0 10px; }
.blog-article p { font-size: 16px; line-height: 1.8; color: #374151; margin-bottom: 16px; }
.blog-article ul, .blog-article ol { margin: 16px 0; padding-left: 24px; }
.blog-article li { font-size: 16px; line-height: 1.8; color: #374151; margin-bottom: 8px; }
.blog-article img { max-width: 100%; width: 100%; height: auto; display: block; margin: 20px auto; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.10); }
.blog-article a { color: #3b82f6; }
.blog-article code {
    background: #f3f4f6;
    padding: 3px 8px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 14px;
}
.blog-article pre {
    background: #1f2937;
    color: #e5e7eb;
    padding: 20px;
    border-radius: 12px;
    overflow-x: auto;
    margin: 20px 0;
}
.blog-article blockquote {
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
    padding: 20px;
    margin: 20px 0;
    border-radius: 0 12px 12px 0;
}
.blog-article table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}
.blog-article th, .blog-article td {
    padding: 12px;
    border: 1px solid #e5e7eb;
    text-align: left;
}
.blog-article th { background: #f9fafb; font-weight: 600; }



/* Rating Widget */
.rating-widget {
    margin-top: 40px;
    padding: 32px;
    background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
    border-radius: 16px;
    text-align: center;
    border: 1px solid #fde68a;
}
.rating-widget h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #92400e;
    margin-bottom: 8px;
}
.rating-widget .rating-subtitle {
    font-size: 14px;
    color: #a16207;
    margin-bottom: 16px;
}
.rating-stars {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 16px;
}
.rating-stars .star {
    font-size: 36px;
    cursor: pointer;
    transition: all 0.15s ease;
    filter: grayscale(1) opacity(0.4);
    user-select: none;
}
.rating-stars .star:hover,
.rating-stars .star.hover {
    filter: grayscale(0) opacity(1);
    transform: scale(1.2);
}
.rating-stars .star.active {
    filter: grayscale(0) opacity(1);
}
.rating-stars .star.selected {
    filter: grayscale(0) opacity(1);
    transform: scale(1.15);
    animation: starPop 0.3s ease;
}
@keyframes starPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1.15); }
}
.rating-info {
    font-size: 15px;
    color: #78350f;
    font-weight: 600;
}
.rating-info .score {
    font-size: 1.5rem;
    font-weight: 800;
    color: #d97706;
}
.rating-thankyou {
    display: none;
    animation: fadeIn 0.3s ease;
}
.rating-thankyou.show { display: block; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
.rating-thankyou p {
    font-size: 16px;
    color: #15803d;
    font-weight: 700;
    margin-bottom: 8px;
}

.blog-share {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #e5e7eb;
}
.blog-share-btn {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
}
.blog-share-btn.facebook { background: #1877f2; color: #fff; }
.blog-share-btn.copy { background: #f3f4f6; color: #374151; }

.blog-sidebar { position: sticky; top: 20px; }
.sidebar-box {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}
.sidebar-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #3b82f6;
}

.related-post {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
    text-decoration: none;
}
.related-post:last-child { border-bottom: none; }
.related-post-img {
    width: 60px;
    height: 45px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 8px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
}
.related-post-title {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    line-height: 1.4;
}
.related-post:hover .related-post-title { color: #3b82f6; }

/* Prev/Next Navigation */
.post-nav {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #e5e7eb;
}
.post-nav-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
}
.post-nav-item:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    transform: translateY(-2px);
}
.post-nav-item.next { text-align: right; }
.post-nav-label { font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
.post-nav-title { font-size: 14px; font-weight: 600; color: #1e40af; line-height: 1.4; }

.popular-post {
    display: flex;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
    text-decoration: none;
    align-items: flex-start;
}
.popular-post:last-child { border-bottom: none; }
.popular-rank {
    width: 26px; height: 26px;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
    flex-shrink: 0;
}
.popular-rank.rank-1 { background: linear-gradient(135deg, #f59e0b, #d97706); }
.popular-rank.rank-2 { background: linear-gradient(135deg, #6b7280, #4b5563); }
.popular-rank.rank-3 { background: linear-gradient(135deg, #b45309, #92400e); }
.popular-info { flex: 1; }
.popular-title { font-size: 13px; font-weight: 600; color: #374151; line-height: 1.4; }
.popular-views { font-size: 11px; color: #94a3b8; margin-top: 2px; }
.popular-post:hover .popular-title { color: #3b82f6; }

/* Table of Contents */
.blog-toc {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
}
.blog-toc-title {
    font-size: 15px;
    font-weight: 700;
    color: #0369a1;
    margin-bottom: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}
.blog-toc-list { list-style: none; padding: 0; margin: 0; }
.blog-toc-list li { margin-bottom: 6px; }
.blog-toc-list li a {
    color: #1e40af;
    text-decoration: none;
    font-size: 14px;
    line-height: 1.5;
    display: block;
    padding: 4px 0;
    border-left: 2px solid transparent;
    padding-left: 0;
    transition: all 0.2s;
}
.blog-toc-list li a:hover {
    color: #3b82f6;
    border-left-color: #3b82f6;
    padding-left: 8px;
}
.blog-toc-list li.toc-h3 a { padding-left: 16px; font-size: 13px; }
.blog-toc-list li.toc-h3 a:hover { padding-left: 24px; }
.blog-toc-list li.toc-h4 a { padding-left: 32px; font-size: 12px; color: #6b7280; }
.blog-toc-list li.toc-h4 a:hover { padding-left: 40px; }

@media (max-width: 768px) {
    .blog-post-title { font-size: 1.5rem; }
    .blog-article { padding: 16px; }
    .blog-post-wrapper { padding: 0 12px; gap: 20px; }
    .post-nav { grid-template-columns: 1fr; }
}
</style>

<!-- Hero -->
<section class="blog-post-hero">
    <div class="container">
        @include('partials.breadcrumbs', ['breadcrumbs' => [
            ['name' => 'Trang chủ', 'url' => url('/')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $post->category, 'url' => route('blog.category', $post->category)],
            ['name' => Str::limit($post->title, 50), 'url' => url()->current()]
        ]])
        <span class="blog-post-category">{{ $post->category }}</span>
        <h1 class="blog-post-title">{{ $post->title }}</h1>
        <div class="blog-post-meta">
            <span>📅 <time datetime="{{ \Carbon\Carbon::parse($post->created_at)->toIso8601String() }}">{{ \Carbon\Carbon::parse($post->created_at)->format('d/m/Y') }}</time></span>
            <span>✍️ {{ $post->author }}</span>
            <span>👁 {{ number_format($post->views) }} lượt xem</span>
            @php
                $readingTime = max(1, ceil(str_word_count(strip_tags($post->content)) / 200));
            @endphp
            <span>⏱️ {{ $readingTime }} phút đọc</span>
        </div>

    </div>
</section>

<!-- Content -->
<section class="blog-post-content">
    <div class="blog-post-wrapper">
        <!-- Article -->
        <article class="blog-article">
            @php
                $isUnlockToolPost = str_contains(strtolower($post->slug), 'unlocktool') || str_contains(strtolower($post->category ?? ''), 'unlocktool') || str_contains(strtolower($post->title), 'unlocktool');
            @endphp
            @if($isUnlockToolPost)
            <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 2px solid #fde047; border-radius: 16px; padding: 24px 20px; margin-bottom: 28px; text-align: center; box-shadow: 0 4px 15px rgba(253, 224, 71, 0.2);">
                <h2 style="font-size: 1.4rem; font-weight: 800; color: #d97706; margin: 0 0 8px 0; border: none; padding: 0;">🔥 THUÊ UNLOCKTOOL CHỈ TỪ 10.000Đ/H</h2>
                <p style="font-size: 15px; color: #92400e; margin: 0 0 18px 0; font-weight: 500;">Tin cậy bởi hơn 15.000+ thợ mobile toàn quốc. Hệ thống tự động 100% - Nhận tài khoản ngay!</p>
                <a href="/thue-unlocktool" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: #fff; text-decoration: none; font-weight: 700; font-size: 16px; padding: 14px 28px; border-radius: 8px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); transition: all 0.2s; text-transform: uppercase;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(239, 68, 68, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(239, 68, 68, 0.3)';">
                    ⚡ Đăng Nhập & Thuê Ngay
                </a>
            </div>
            @endif

            @if(isset($toc) && count($toc) >= 3)
            <nav class="blog-toc">
                <div class="blog-toc-title" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                    📑 Mục lục bài viết <span style="font-size:11px;color:#94a3b8;">▼</span>
                </div>
                <ul class="blog-toc-list">
                    @foreach($toc as $item)
                    <li class="toc-h{{ $item['level'] }}">
                        <a href="#{{ $item['id'] }}">{{ $item['text'] }}</a>
                    </li>
                    @endforeach
                </ul>
            </nav>
            @endif
            {!! $post->content !!}

            {{-- Author Bio - E-E-A-T Signal --}}
            <div style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid #e2e8f0;border-radius:16px;padding:24px;margin:30px 0;display:flex;gap:16px;align-items:flex-start">
                <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#1d4ed8);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-size:22px;font-weight:bold">TK</div>
                <div>
                    <p style="margin:0 0 4px;font-weight:700;color:#1e293b;font-size:15px">Về tác giả — ThueTaiKhoan.net</p>
                    <p style="margin:0 0 8px;color:#64748b;font-size:13px">Nền tảng cho thuê tool GSM & Vietmap tự động 24/7</p>
                    <p style="margin:0;color:#475569;font-size:13px;line-height:1.6">Hệ thống phục vụ hàng trăm kỹ thuật viên và người dùng cá nhân trên toàn quốc. Bài viết được biên soạn dựa trên kinh nghiệm vận hành thực tế, dữ liệu từ hệ thống, và phản hồi từ cộng đồng khách hàng. Liên hệ: <a href="https://zalo.me/0777333763" style="color:#3b82f6">Zalo hỗ trợ</a></p>
                </div>
            </div>



            <!-- Rating Widget -->
            <div class="rating-widget" id="rating-widget">
                <h3>⭐ Bạn thấy bài viết hữu ích không?</h3>
                <p class="rating-subtitle">Hãy đánh giá để giúp chúng tôi cải thiện nội dung</p>
                
                <div class="rating-stars" id="rating-stars">
                    @for($i = 1; $i <= 5; $i++)
                    <span class="star {{ $hasRated ? 'active' : '' }}" data-value="{{ $i }}" onclick="submitRating({{ $i }})" onmouseenter="hoverStars({{ $i }})" onmouseleave="resetStars()">⭐</span>
                    @endfor
                </div>
                
                <div class="rating-info">
                    <span class="score" id="rating-avg">{{ $ratingAvg }}</span>/5
                    <span style="margin-left:8px;font-weight:400;color:#92400e;">(<span id="rating-count">{{ $ratingCount }}</span> đánh giá)</span>
                </div>
                
                <div class="rating-thankyou" id="rating-thankyou">
                    <p>✅ Cảm ơn bạn đã đánh giá!</p>
                </div>
            </div>

            <!-- Share -->
            <div class="blog-share">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="blog-share-btn facebook">
                    📘 Chia sẻ Facebook
                </a>
                <button class="blog-share-btn copy" onclick="navigator.clipboard.writeText(window.location.href); alert('Đã copy link!');">
                    📋 Copy link
                </button>
            </div>
            
            <!-- Prev/Next Navigation -->
            @if($prevPost || $nextPost)
            <nav class="post-nav">
                @if($prevPost)
                <a href="{{ route('blog.show', $prevPost->slug) }}" class="post-nav-item prev">
                    <span class="post-nav-label">← Bài trước</span>
                    <span class="post-nav-title">{{ Str::limit($prevPost->title, 60) }}</span>
                </a>
                @else
                <div></div>
                @endif
                @if($nextPost)
                <a href="{{ route('blog.show', $nextPost->slug) }}" class="post-nav-item next">
                    <span class="post-nav-label">Bài sau →</span>
                    <span class="post-nav-title">{{ Str::limit($nextPost->title, 60) }}</span>
                </a>
                @endif
            </nav>
            @endif
        </article>
        
        <!-- Sidebar -->
        <aside class="blog-sidebar">
            <!-- CTA -->
            <div class="sidebar-box" style="background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;">
                <h3 style="font-size: 1.1rem; margin-bottom: 10px;">🔓 Thuê Tool GSM</h3>
                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 16px;">Giá chỉ từ 10.000đ - Nhận tài khoản ngay!</p>
                <a href="/" style="display: block; background: #fff; color: #ea580c; padding: 12px; text-align: center; border-radius: 10px; font-weight: 700; text-decoration: none;">
                    Xem dịch vụ →
                </a>
            </div>
            
            {{-- Video Hướng Dẫn in Sidebar --}}
            @php
                $slug = $post->slug ?? '';
                $cat = strtolower($post->category ?? '');
                $sidebarVideo = null;
                if (str_contains($slug, 'unlocktool') || str_contains($slug, 'frp') || str_contains($slug, 'unlock') || str_contains($cat, 'unlocktool')) {
                    $sidebarVideo = ['id' => '_WKNj1lZyQ4', 'title' => 'Thuê UnlockTool Giá Rẻ 10K/6H'];
                } elseif (str_contains($slug, 'vietmap') || str_contains($cat, 'vietmap')) {
                    $sidebarVideo = ['id' => '1QSLm8xn1WU', 'title' => 'Hướng dẫn thuê Vietmap Live'];
                }
            @endphp
            @if($sidebarVideo)
            <div class="sidebar-box sidebar-video-box" style="padding: 0; overflow: hidden; border-radius: 12px;">
                <div style="position: relative; aspect-ratio: 9/16; background: #000;">
                    <iframe 
                        src="https://www.youtube.com/embed/{{ $sidebarVideo['id'] }}?rel=0" 
                        title="{{ $sidebarVideo['title'] }}"
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen
                        loading="lazy"
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                    ></iframe>
                </div>
                <div style="padding: 12px; text-align: center; background: #f8fafc;">
                    <span style="font-size: 13px; color: #64748b;">🎬 {{ $sidebarVideo['title'] }}</span>
                </div>
            </div>
            @endif
            
            @if($relatedPosts->isNotEmpty())
            <!-- Related -->
            <div class="sidebar-box">
                <h3 class="sidebar-title">📰 Bài viết liên quan</h3>
                @foreach($relatedPosts as $related)
                <a href="{{ route('blog.show', $related->slug) }}" class="related-post">
                    <div class="related-post-img">📄</div>
                    <div class="related-post-title">{{ Str::limit($related->title, 60) }}</div>
                </a>
                @endforeach
            </div>
            @endif
            
            @if(isset($popularPosts) && $popularPosts->isNotEmpty())
            <!-- Popular Posts -->
            <div class="sidebar-box">
                <h3 class="sidebar-title">🔥 Bài viết phổ biến</h3>
                @foreach($popularPosts as $index => $popular)
                <a href="{{ route('blog.show', $popular->slug) }}" class="popular-post">
                    <span class="popular-rank rank-{{ $index + 1 }}">{{ $index + 1 }}</span>
                    <div class="popular-info">
                        <div class="popular-title">{{ Str::limit($popular->title, 55) }}</div>
                        <div class="popular-views">👁 {{ number_format($popular->views) }} lượt xem</div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
            
            <!-- Back to Blog -->
            <div class="sidebar-box" style="text-align: center;">
                <a href="{{ route('blog.index') }}" style="color: #3b82f6; text-decoration: none; font-weight: 600;">
                    ← Quay lại Blog
                </a>
            </div>
        </aside>
    </div>
</section>

<script>
// Rating system
let currentRating = {{ $hasRated ? $ratingAvg : 0 }};
let hasRated = {{ $hasRated ? 'true' : 'false' }};

function hoverStars(n) {
    if (hasRated) return;
    const stars = document.querySelectorAll('#rating-stars .star');
    stars.forEach((s, i) => {
        s.classList.toggle('hover', i < n);
    });
}

function resetStars() {
    if (hasRated) return;
    const stars = document.querySelectorAll('#rating-stars .star');
    stars.forEach(s => s.classList.remove('hover'));
}

function submitRating(value) {
    if (hasRated) return;
    
    const stars = document.querySelectorAll('#rating-stars .star');
    stars.forEach((s, i) => {
        s.classList.remove('hover');
        s.classList.toggle('selected', i < value);
    });
    
    fetch('{{ route("blog.rate", $post->slug) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ rating: value })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hasRated = true;
            document.getElementById('rating-avg').textContent = data.avg;
            document.getElementById('rating-count').textContent = data.count;
            document.getElementById('rating-thankyou').classList.add('show');
            
            stars.forEach(s => {
                s.style.cursor = 'default';
                s.classList.add('active');
            });
        }
    })
    .catch(() => {
        alert('Có lỗi xảy ra. Vui lòng thử lại!');
        stars.forEach(s => s.classList.remove('selected'));
    });
}
</script>
@endsection
