@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $post->meta_description ?: Str::limit(strip_tags($post->content), 160))
@if(!empty($post->robots_meta) && $post->robots_meta !== 'index, follow')
@section('robots_meta', $post->robots_meta)
@endif

@section('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": "{{ e($post->meta_title ?: $post->title) }}",
    "description": "{{ e($post->meta_description ?: Str::limit(strip_tags($post->content), 160)) }}",
    "image": "{{ $post->image ? url($post->image) : asset('images/og-default.png') }}",
    "author": {
        "@@type": "Organization",
        "name": "UnlockTool.us",
        "url": "{{ url('/') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "UnlockTool.us",
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
    }
    @if($ratingCount > 0)
    ,"aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "{{ $ratingAvg }}",
        "bestRating": "5",
        "worstRating": "1",
        "ratingCount": "{{ $ratingCount }}"
    }
    @endif
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {"@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
        {"@@type": "ListItem", "position": 2, "name": "Blog", "item": "{{ route('blog.index') }}"},
        {"@@type": "ListItem", "position": 3, "name": "{{ e($post->category) }}", "item": "{{ route('blog.category', $post->category) }}"},
        {"@@type": "ListItem", "position": 4, "name": "{{ e($post->title) }}"}
    ]
}
</script>
@if(isset($faqSchema))
<script type="application/ld+json">{!! $faqSchema !!}</script>
@endif
@if(isset($howToSchema))
<script type="application/ld+json">{!! $howToSchema !!}</script>
@endif
@stop

@section('content')
<style>
.blog-post-hero { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #8b5cf6 100%); padding: 60px 0; color: #fff; }
.blog-post-hero .container { max-width: 900px; }
.blog-breadcrumb { display: flex; gap: 8px; font-size: 14px; margin-bottom: 20px; opacity: 0.9; flex-wrap: wrap; }
.blog-breadcrumb a { color: #fff; text-decoration: none; }
.blog-post-category { display: inline-block; background: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 16px; backdrop-filter: blur(6px); }
.blog-post-title { font-size: 2.2rem; font-weight: 800; line-height: 1.3; margin-bottom: 20px; text-shadow: 0 2px 8px rgba(0,0,0,0.15); }
.blog-post-meta { display: flex; gap: 20px; flex-wrap: wrap; font-size: 14px; opacity: 0.9; }
.blog-post-content { background: #f8fafc; padding: 50px 0 80px; }
.blog-post-wrapper { display: grid; grid-template-columns: 1fr 300px; gap: 40px; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
@media (max-width: 900px) { .blog-post-wrapper { grid-template-columns: 1fr; } }

/* Article Premium Styles */
.blog-article { background: #fff; border-radius: 16px; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow-x: hidden; word-wrap: break-word; min-width: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
.blog-article h2 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 36px 0 16px; padding: 16px 20px; background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border-left: 4px solid #3b82f6; border-radius: 0 12px 12px 0; }
.blog-article h2:first-of-type { margin-top: 0; }
.blog-article h3 { font-size: 1.2rem; font-weight: 700; color: #1e40af; margin: 28px 0 12px; padding-left: 14px; border-left: 3px solid #60a5fa; }
.blog-article h4 { font-size: 1.05rem; font-weight: 700; color: #334155; margin: 20px 0 10px; }
.blog-article p { font-size: 16px; line-height: 1.9; color: #374151; margin-bottom: 18px; }
.blog-article strong { color: #1e293b; font-weight: 700; }
.blog-article ul, .blog-article ol { margin: 18px 0; padding-left: 0; list-style: none; }
.blog-article ul li { font-size: 16px; line-height: 1.8; color: #374151; margin-bottom: 10px; padding: 8px 12px 8px 36px; position: relative; background: #f8fafc; border-radius: 8px; border-left: 3px solid #3b82f6; }
.blog-article ul li::before { content: '✅'; position: absolute; left: 10px; top: 8px; font-size: 14px; }
.blog-article ol { counter-reset: step; }
.blog-article ol li { font-size: 16px; line-height: 1.8; color: #374151; margin-bottom: 10px; padding: 8px 12px 8px 42px; position: relative; background: #fefce8; border-radius: 8px; border-left: 3px solid #f59e0b; counter-increment: step; }
.blog-article ol li::before { content: counter(step); position: absolute; left: 8px; top: 6px; width: 26px; height: 26px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border-radius: 50%; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.blog-article img { max-width: 100%; height: auto; display: block; margin: 24px auto; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.blog-article a { color: #2563eb; font-weight: 500; text-decoration: underline; text-underline-offset: 3px; transition: color 0.2s; }
.blog-article a:hover { color: #1d4ed8; }
.blog-article table { width: 100%; border-collapse: collapse; margin: 24px 0; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.blog-article th { padding: 14px 16px; background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff; font-weight: 700; font-size: 14px; text-align: left; }
.blog-article td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 15px; }
.blog-article tr:nth-child(even) td { background: #f8fafc; }
.blog-article tr:hover td { background: #eff6ff; }
.blog-article blockquote { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; padding: 20px 24px; margin: 24px 0; border-radius: 0 14px 14px 0; font-style: italic; color: #1e40af; box-shadow: 0 2px 8px rgba(59,130,246,0.1); }
.blog-article code { background: #f1f5f9; padding: 2px 8px; border-radius: 6px; font-size: 14px; color: #dc2626; font-weight: 600; }
.blog-article pre { background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 12px; overflow-x: auto; margin: 20px 0; font-size: 14px; }
.blog-article pre code { background: none; color: inherit; padding: 0; }
.blog-article hr { border: none; height: 3px; background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899); border-radius: 2px; margin: 32px 0; }

/* Rating Widget */
.rating-widget { margin-top: 40px; padding: 32px; background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%); border-radius: 16px; text-align: center; border: 1px solid #fde68a; }
.rating-widget h3 { font-size: 1.2rem; font-weight: 700; color: #92400e; margin-bottom: 8px; }
.rating-stars { display: flex; justify-content: center; gap: 8px; margin: 16px 0; }
.rating-stars .star { font-size: 36px; cursor: pointer; transition: all 0.15s; filter: grayscale(1) opacity(0.4); user-select: none; }
.rating-stars .star:hover, .rating-stars .star.hover { filter: grayscale(0) opacity(1); transform: scale(1.2); }
.rating-stars .star.selected { filter: grayscale(0) opacity(1); transform: scale(1.15); }
.rating-info { font-size: 15px; color: #78350f; font-weight: 600; }
.rating-info .score { font-size: 1.5rem; font-weight: 800; color: #d97706; }
.rating-thankyou { display: none; }
.rating-thankyou.show { display: block; }
.rating-thankyou p { font-size: 16px; color: #15803d; font-weight: 700; margin-bottom: 8px; }
.blog-share { display: flex; gap: 12px; margin-top: 30px; padding-top: 30px; border-top: 1px solid #e5e7eb; }
.blog-share-btn { padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 8px; border: none; cursor: pointer; transition: all 0.2s; }
.blog-share-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.blog-share-btn.facebook { background: #1877f2; color: #fff; }
.blog-share-btn.copy { background: #f3f4f6; color: #374151; }
.blog-sidebar { position: sticky; top: 20px; }
.sidebar-box { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 24px; }
.sidebar-title { font-size: 1rem; font-weight: 700; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #3b82f6; }
.related-post { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f3f4f6; text-decoration: none; transition: all 0.2s; }
.related-post:last-child { border-bottom: none; }
.related-post:hover { padding-left: 6px; }
.related-post-img { width: 60px; height: 45px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px; }
.related-post-title { font-size: 13px; font-weight: 600; color: #374151; line-height: 1.4; }
.related-post:hover .related-post-title { color: #3b82f6; }
.post-nav { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 30px; padding-top: 30px; border-top: 1px solid #e5e7eb; }
.post-nav-item { display: flex; flex-direction: column; gap: 6px; padding: 16px; background: #f8fafc; border-radius: 12px; text-decoration: none; transition: all 0.3s; border: 1px solid #e5e7eb; }
.post-nav-item:hover { background: #eff6ff; border-color: #93c5fd; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.12); }
.post-nav-item.next { text-align: right; }
.post-nav-label { font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; }
.post-nav-title { font-size: 14px; font-weight: 600; color: #1e40af; line-height: 1.4; }

/* TOC Premium */
.blog-toc { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; border-radius: 14px; padding: 24px; margin-bottom: 28px; }
.blog-toc-title { font-size: 16px; font-weight: 700; color: #0369a1; margin-bottom: 14px; cursor: pointer; display: flex; align-items: center; gap: 8px; }
.blog-toc-list { list-style: none !important; padding: 0 !important; margin: 0; }
.blog-toc-list li { margin-bottom: 4px; background: none !important; border-left: none !important; padding: 0 !important; }
.blog-toc-list li::before { display: none !important; }
.blog-toc-list li a { color: #1e40af; text-decoration: none; font-size: 14px; line-height: 1.6; display: block; padding: 6px 12px; border-radius: 8px; transition: all 0.2s; }
.blog-toc-list li a:hover { color: #fff; background: #3b82f6; padding-left: 16px; }
.blog-toc-list li.toc-h3 a { padding-left: 28px; font-size: 13px; color: #64748b; }
.blog-toc-list li.toc-h3 a:hover { padding-left: 32px; color: #fff; }

/* Popular Posts */
.popular-post { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; text-decoration: none; transition: all 0.2s; }
.popular-post:last-child { border-bottom: none; }
.popular-post:hover { padding-left: 4px; }
.popular-rank { width: 26px; height: 26px; background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.popular-title { font-size: 13px; font-weight: 600; color: #374151; line-height: 1.4; }
.popular-views { font-size: 11px; color: #94a3b8; margin-top: 2px; }
.popular-post:hover .popular-title { color: #3b82f6; }

@media (max-width: 768px) { .blog-post-title { font-size: 1.5rem; } .blog-article { padding: 20px; } .post-nav { grid-template-columns: 1fr; } .blog-article h2 { font-size: 1.25rem; padding: 14px 16px; } }
</style>

<section class="blog-post-hero">
    <div class="container">
        <nav class="blog-breadcrumb">
            <a href="{{ url('/') }}">Trang chủ</a> › <a href="{{ route('blog.index') }}">Blog</a> › <a href="{{ route('blog.category', $post->category) }}">{{ $post->category }}</a> › <span>{{ Str::limit($post->title, 50) }}</span>
        </nav>
        <span class="blog-post-category">{{ $post->category }}</span>
        <h1 class="blog-post-title">{{ $post->title }}</h1>
        <div class="blog-post-meta">
            <span>📅 <time datetime="{{ \Carbon\Carbon::parse($post->created_at)->toIso8601String() }}">{{ \Carbon\Carbon::parse($post->created_at)->format('d/m/Y') }}</time></span>
            <span>✍️ {{ $post->author }}</span>
            <span>👁 {{ number_format($post->views) }} lượt xem</span>
            @php $readingTime = max(1, ceil(str_word_count(strip_tags($post->content)) / 200)); @endphp
            <span>⏱️ {{ $readingTime }} phút đọc</span>
        </div>
    </div>
</section>

<section class="blog-post-content">
    <div class="blog-post-wrapper">
        <article class="blog-article">
            @if(isset($toc) && count($toc) >= 3)
            <nav class="blog-toc">
                <div class="blog-toc-title" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                    📑 Mục lục bài viết <span style="font-size:11px;color:#94a3b8;">▼</span>
                </div>
                <ul class="blog-toc-list">
                    @foreach($toc as $item)
                    <li class="toc-h{{ $item['level'] }}"><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
            @endif
            
            {!! $post->content !!}
            
            <!-- Rating Widget -->
            <div class="rating-widget" id="rating-widget">
                <h3>⭐ Bài viết có hữu ích không?</h3>
                <p style="font-size: 14px; color: #a16207; margin-bottom: 16px;">Đánh giá để giúp chúng tôi cải thiện nội dung</p>
                <div class="rating-stars" id="rating-stars">
                    @for($i = 1; $i <= 5; $i++)
                    <span class="star {{ $hasRated ? 'selected' : '' }}" data-value="{{ $i }}" onclick="submitRating({{ $i }})" onmouseenter="hoverStars({{ $i }})" onmouseleave="resetStars()">⭐</span>
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
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="blog-share-btn facebook">📘 Chia sẻ Facebook</a>
                <button class="blog-share-btn copy" onclick="navigator.clipboard.writeText(window.location.href); alert('Đã copy link!');">📋 Sao chép link</button>
            </div>
            
            <!-- Prev/Next -->
            @if($prevPost || $nextPost)
            <nav class="post-nav">
                @if($prevPost)
                <a href="{{ route('blog.show', $prevPost->slug) }}" class="post-nav-item prev">
                    <span class="post-nav-label">← Bài trước</span>
                    <span class="post-nav-title">{{ Str::limit($prevPost->title, 60) }}</span>
                </a>
                @else <div></div> @endif
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
            <div class="sidebar-box" style="background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;">
                <h3 style="font-size: 1.1rem; margin-bottom: 10px;">🔓 Thuê UnlockTool</h3>
                <p style="font-size: 14px; opacity: 0.9; margin-bottom: 16px;">Chỉ từ 10.000đ — Nhận tài khoản tự động ngay!</p>
                <a href="/" style="display: block; background: #fff; color: #ea580c; padding: 12px; text-align: center; border-radius: 10px; font-weight: 700; text-decoration: none;">Xem dịch vụ →</a>
            </div>
            
            @if($relatedPosts->isNotEmpty())
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
            <div class="sidebar-box">
                <h3 class="sidebar-title">🔥 Bài viết phổ biến</h3>
                @foreach($popularPosts as $index => $popular)
                <a href="{{ route('blog.show', $popular->slug) }}" class="popular-post">
                    <span class="popular-rank">{{ $index + 1 }}</span>
                    <div>
                        <div class="popular-title">{{ Str::limit($popular->title, 55) }}</div>
                        <div class="popular-views">👁 {{ number_format($popular->views) }} lượt xem</div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
            
            <div class="sidebar-box" style="text-align: center;">
                <a href="{{ route('blog.index') }}" style="color: #3b82f6; text-decoration: none; font-weight: 600;">← Quay lại Blog</a>
            </div>
        </aside>
    </div>
</section>

<script>
let hasRated = {{ $hasRated ? 'true' : 'false' }};
function hoverStars(n) { if (hasRated) return; document.querySelectorAll('#rating-stars .star').forEach((s, i) => s.classList.toggle('hover', i < n)); }
function resetStars() { if (hasRated) return; document.querySelectorAll('#rating-stars .star').forEach(s => s.classList.remove('hover')); }
function submitRating(value) {
    if (hasRated) return;
    const stars = document.querySelectorAll('#rating-stars .star');
    stars.forEach((s, i) => { s.classList.remove('hover'); s.classList.toggle('selected', i < value); });
    fetch('{{ route("blog.rate", $post->slug) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ rating: value })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hasRated = true;
            document.getElementById('rating-avg').textContent = data.avg;
            document.getElementById('rating-count').textContent = data.count;
            document.getElementById('rating-thankyou').classList.add('show');
            stars.forEach(s => { s.style.cursor = 'default'; });
        }
    })
    .catch(() => { alert('Error. Please try again!'); stars.forEach(s => s.classList.remove('selected')); });
}
</script>
@endsection
