@extends('admin.layouts.app')
@section('title', 'SEO Analyzer')

@section('content')
<style>
.seo-tabs { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 24px; overflow-x: auto; }
.seo-tab { padding: 12px 20px; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; white-space: nowrap; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
.seo-tab:hover { color: #3b82f6; }
.seo-tab.active { color: #3b82f6; border-bottom-color: #3b82f6; }
.seo-tab .badge { background: #ef4444; color: #fff; font-size: 11px; padding: 2px 7px; border-radius: 10px; font-weight: 700; }
.seo-tab .badge.warn { background: #f59e0b; }
.seo-tab .badge.good { background: #22c55e; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.stat-icon.blue { background: #dbeafe; }
.stat-icon.green { background: #dcfce7; }
.stat-icon.orange { background: #ffedd5; }
.stat-icon.red { background: #fee2e2; }
.stat-icon.purple { background: #f3e8ff; }
.stat-icon.cyan { background: #cffafe; }
.stat-value { font-size: 26px; font-weight: 800; color: #1e293b; }
.stat-label { font-size: 12px; color: #64748b; margin-top: 2px; }

.score-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; color: #fff; margin: 0 auto 8px; }
.score-circle.good { background: linear-gradient(135deg, #22c55e, #16a34a); }
.score-circle.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.score-circle.bad { background: linear-gradient(135deg, #ef4444, #dc2626); }

.seo-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.seo-table th { background: #f8fafc; padding: 10px 12px; text-align: left; font-weight: 600; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
.seo-table th.sortable { cursor: pointer; user-select: none; transition: color 0.2s; }
.seo-table th.sortable:hover { color: #3b82f6; }
.seo-table th.sortable .sort-icon { margin-left: 4px; font-size: 10px; opacity: 0.4; }
.seo-table th.sortable.active .sort-icon { opacity: 1; color: #3b82f6; }
.seo-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.seo-table tr:hover td { background: #f8fafc; }
.seo-table .score-badge { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; font-size: 12px; font-weight: 700; color: #fff; }
.seo-table .score-badge.good { background: #22c55e; }
.seo-table .score-badge.warning { background: #f59e0b; }
.seo-table .score-badge.bad { background: #ef4444; }

.urgency-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.urgency-badge.critical { background: #fee2e2; color: #dc2626; }
.urgency-badge.high { background: #ffedd5; color: #ea580c; }
.urgency-badge.medium { background: #fef3c7; color: #d97706; }
.urgency-badge.low { background: #f0fdf4; color: #16a34a; }

.cannib-group { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.cannib-keyword { font-size: 16px; font-weight: 700; color: #dc2626; margin-bottom: 8px; }
.cannib-count { font-size: 12px; color: #64748b; margin-left: 8px; }
.cannib-post { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
.cannib-post:last-child { border-bottom: none; }

.bulk-input { width: 100%; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 13px; background: #fff; }
.bulk-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

.btn-sm { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: #fff; }
.btn-primary:hover { background: #2563eb; }
.btn-success { background: #22c55e; color: #fff; }
.btn-success:hover { background: #16a34a; }

.progress-bar { height: 6px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 4px; transition: width 0.3s; }
.progress-fill.good { background: linear-gradient(90deg, #22c55e, #16a34a); }
.progress-fill.warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
.progress-fill.bad { background: linear-gradient(90deg, #ef4444, #dc2626); }

.analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
.analytics-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.analytics-title { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 12px; }

.filter-bar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
.filter-bar select, .filter-bar input { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; }
.empty-state { text-align: center; padding: 40px; color: #94a3b8; }
.empty-state .icon { font-size: 48px; margin-bottom: 12px; }
</style>

<div style="padding: 24px;">
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 4px;">🔍 SEO Analyzer <span style="color: #22c55e; font-size: 14px;">PRO</span></h2>
        <p style="color: #64748b; font-size: 14px;">Vượt Yoast SEO Premium — Phân tích, tối ưu & giám sát SEO toàn diện</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <button class="btn-sm btn-success" onclick="previewAutoFixV2()" id="autofix-btn" style="display: flex; align-items: center; gap: 6px; padding: 10px 18px;">
            🔍 Auto-Fix V2 Preview
        </button>
        <a href="{{ route('admin.seo.redirects') }}" class="btn-sm" style="text-decoration: none; display: flex; align-items: center; gap: 6px; padding: 10px 18px; background: #f1f5f9; color: #475569; border: 1px solid #e5e7eb;">
            ↪️ 301 Redirects
        </a>
        <a href="{{ route('admin.seo.export-keywords') }}" class="btn-sm btn-primary" style="text-decoration: none; display: flex; align-items: center; gap: 6px; padding: 10px 18px;">
            📥 Export Keywords CSV
        </a>
    </div>
</div>

    {{-- Stats Overview --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📊</div>
            <div><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Tổng bài viết</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div><div class="stat-value">{{ $stats['good'] }}</div><div class="stat-label">SEO Tốt (70+)</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">⚠️</div>
            <div><div class="stat-value">{{ $stats['warning'] }}</div><div class="stat-label">Cần cải thiện (40-69)</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">❌</div>
            <div><div class="stat-value">{{ $stats['bad'] }}</div><div class="stat-label">SEO Yếu (<40)</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">📝</div>
            <div><div class="stat-value">{{ $analytics['keyword_coverage'] ?? 0 }}%</div><div class="stat-label">Có Focus Keyword</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">📋</div>
            <div><div class="stat-value">{{ $analytics['meta_coverage'] ?? 0 }}%</div><div class="stat-label">Có Meta Desc</div></div>
        </div>
    </div>

    {{-- Average Score + Analytics --}}
    <div class="analytics-grid">
        <div class="analytics-card" style="text-align: center;">
            <div class="score-circle {{ $stats['avgStatus'] }}">{{ $stats['avgScore'] }}</div>
            <div class="analytics-title">Điểm SEO Trung Bình</div>
            <p style="font-size: 12px; color: #64748b;">Dựa trên phân tích {{ $stats['total'] }} bài viết</p>
        </div>
        <div class="analytics-card">
            <div class="analytics-title">📊 Tổng quan Coverage</div>
            <div style="margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;"><span>Focus Keyword</span><span>{{ $analytics['with_keyword'] ?? 0 }}/{{ $analytics['total_posts'] ?? 0 }}</span></div>
                <div class="progress-bar"><div class="progress-fill {{ ($analytics['keyword_coverage'] ?? 0) >= 70 ? 'good' : (($analytics['keyword_coverage'] ?? 0) >= 40 ? 'warning' : 'bad') }}" style="width: {{ $analytics['keyword_coverage'] ?? 0 }}%"></div></div>
            </div>
            <div style="margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;"><span>Meta Description</span><span>{{ $analytics['with_meta_desc'] ?? 0 }}/{{ $analytics['total_posts'] ?? 0 }}</span></div>
                <div class="progress-bar"><div class="progress-fill {{ ($analytics['meta_coverage'] ?? 0) >= 70 ? 'good' : (($analytics['meta_coverage'] ?? 0) >= 40 ? 'warning' : 'bad') }}" style="width: {{ $analytics['meta_coverage'] ?? 0 }}%"></div></div>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;"><span>Meta Title</span><span>{{ $analytics['with_meta_title'] ?? 0 }}/{{ $analytics['total_posts'] ?? 0 }}</span></div>
                <div class="progress-bar"><div class="progress-fill {{ (($analytics['with_meta_title'] ?? 0) / max($analytics['total_posts'] ?? 1, 1) * 100) >= 70 ? 'good' : 'warning' }}" style="width: {{ ($analytics['with_meta_title'] ?? 0) / max($analytics['total_posts'] ?? 1, 1) * 100 }}%"></div></div>
            </div>
        </div>
        <div class="analytics-card">
            <div class="analytics-title">📈 Thống kê nhanh</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px;">
                <div>📝 Trung bình từ: <strong>{{ $analytics['avg_word_count'] ?? 0 }}</strong></div>
                <div>⏳ Bài cũ >6T: <strong style="color: #ea580c;">{{ $analytics['stale_count'] ?? 0 }}</strong></div>
                <div>🔗 Bài cô lập: <strong style="color: #dc2626;">{{ count($orphanedContent) }}</strong></div>
                <div>⚔️ Trùng KW: <strong style="color: #dc2626;">{{ count($cannibalization) }}</strong></div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="seo-tabs">
        <div class="seo-tab active" data-tab="overview">📋 Tổng quan</div>
        <div class="seo-tab" data-tab="stale">⏳ Bài cũ<span class="badge warn">{{ count($staleContent) }}</span></div>
        <div class="seo-tab" data-tab="orphaned">🔗 Cô lập<span class="badge">{{ count($orphanedContent) }}</span></div>
        <div class="seo-tab" data-tab="cannibalization">⚔️ Trùng Keyword<span class="badge">{{ count($cannibalization) }}</span></div>
        <div class="seo-tab" data-tab="bulk">✏️ Bulk Editor</div>
        <div class="seo-tab" data-tab="recent">🆕 Bài mới thêm<span class="badge good">{{ $recentlyAdded->count() }}</span></div>
        <div class="seo-tab" data-tab="decay" onclick="loadContentDecay()">📉 Content Decay</div>
        <div class="seo-tab" data-tab="broken" onclick="loadBrokenLinks()">🔗 Broken Links</div>
        <div class="seo-tab" data-tab="authority" onclick="loadTopicalAuthority()">🗺️ Topical Map</div>
    </div>

    {{-- Tab: Overview --}}
    <div class="tab-panel active" id="tab-overview">
        <div class="filter-bar">
            <select id="seo-filter" onchange="filterPosts(this.value)">
                <option value="all" {{ request('filter') == 'all' ? 'selected' : '' }}>🔵 Tất ({{ $stats['total'] }})</option>
                <option value="good" {{ request('filter') == 'good' ? 'selected' : '' }}>🟢 SEO Tốt</option>
                <option value="warning" {{ request('filter') == 'warning' ? 'selected' : '' }}>🟠 Cần cải thiện</option>
                <option value="bad" {{ request('filter') == 'bad' ? 'selected' : '' }}>🔴 SEO Yếu</option>
                <option value="no-keyword" {{ request('filter') == 'no-keyword' ? 'selected' : '' }}>⬜ Chưa có keyword</option>
            </select>
            <select id="seo-sort" onchange="sortPosts(this.value)">
                <option value="">📊 Sắp xếp</option>
                <option value="score-asc">Điểm: Thấp → Cao</option>
                <option value="score-desc">Điểm: Cao → Thấp</option>
                <option value="id-asc">ID: Cũ → Mới</option>
                <option value="id-desc">ID: Mới → Cũ</option>
            </select>
            <input type="text" id="seo-search" placeholder="Tìm kiếm bài viết..." oninput="searchPosts(this.value)">
        </div>
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
            <table class="seo-table">
                <thead><tr>
                    <th class="sortable" onclick="sortTable(0,'num')"># <span class="sort-icon">▼</span></th><th>Bài viết</th><th>Từ khóa</th><th class="sortable active" onclick="sortTable(3,'num')">Điểm <span class="sort-icon">▼</span></th><th>Meta Title</th><th>Meta Desc</th><th>Ảnh</th><th>Hành động</th>
                </tr></thead>
                <tbody>
                @foreach($posts as $i => $post)
                <tr class="post-row" data-title="{{ mb_strtolower($post->title) }}" data-status="{{ $post->seo_status }}" data-score="{{ $post->seo_score }}">
                    <td>{{ $post->id }}</td>
                    <td>
                        <div style="font-weight: 600; max-width: 300px;">{{ Str::limit($post->title, 50) }}</div>
                        <div style="font-size: 11px; color: #94a3b8;">/blog/{{ Str::limit($post->slug, 40) }}</div>
                    </td>
                    <td><span style="font-size: 12px; color: {{ !empty($post->focus_keyword) ? '#059669' : '#94a3b8' }};">{{ $post->focus_keyword ?: '— Chưa đặt' }}</span></td>
                    <td><span class="score-badge {{ $post->seo_status }}">{{ $post->seo_score }}</span></td>
                    <td>{!! !empty($post->meta_title) ? '<span style="color:#22c55e">✓ '.mb_strlen($post->meta_title).' ký tự</span>' : '<span style="color:#ef4444">✗</span>' !!}</td>
                    <td>{!! !empty($post->meta_description) ? '<span style="color:#22c55e">✓ '.mb_strlen($post->meta_description).' ký tự</span>' : '<span style="color:#ef4444">✗</span>' !!}</td>
                    <td>{{ $post->seo_stats['imageCount'] ?? 0 }}</td>
                    <td><a href="{{ route('admin.blog.edit', $post->id) }}" class="btn-sm btn-primary">✏️ Sửa</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Stale Content --}}
    <div class="tab-panel" id="tab-stale">
        @if(count($staleContent) > 0)
        <div style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <strong>⏳ {{ count($staleContent) }} bài viết</strong> chưa cập nhật >6 tháng. Google ưu tiên nội dung mới — hãy refresh các bài quan trọng!
        </div>
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
            <table class="seo-table">
                <thead><tr><th>#</th><th>Bài viết</th><th>Cập nhật lần cuối</th><th>Đã qua</th><th>Mức độ</th><th>Lượt xem</th><th>Hành động</th></tr></thead>
                <tbody>
                @foreach($staleContent as $post)
                <tr>
                    <td>{{ $post['id'] }}</td>
                    <td style="font-weight: 600; max-width: 300px;">{{ Str::limit($post['title'], 50) }}</td>
                    <td>{{ $post['last_updated'] }}</td>
                    <td><strong>{{ $post['months_since'] }} tháng</strong> ({{ $post['days_since'] }} ngày)</td>
                    <td><span class="urgency-badge {{ $post['urgency'] }}">{{ ucfirst($post['urgency']) }}</span></td>
                    <td>{{ number_format($post['views']) }}</td>
                    <td><a href="{{ route('admin.blog.edit', $post['id']) }}" class="btn-sm btn-primary">✏️ Refresh</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state"><div class="icon">🎉</div><h3>Tuyệt vời!</h3><p>Tất cả bài viết đều được cập nhật trong 6 tháng qua.</p></div>
        @endif
    </div>

    {{-- Tab: Orphaned Content --}}
    <div class="tab-panel" id="tab-orphaned">
        @if(count($orphanedContent) > 0)
        <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <strong>🔗 {{ count($orphanedContent) }} bài viết "cô lập"</strong> — không có internal link nào trỏ đến. Google khó tìm thấy các bài này. Hãy thêm link từ bài khác!
        </div>
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
            <table class="seo-table">
                <thead><tr><th>#</th><th>Bài viết</th><th>URL</th><th>Lượt xem</th><th>Link đến</th><th>Hành động</th></tr></thead>
                <tbody>
                @foreach($orphanedContent as $post)
                <tr>
                    <td>{{ $post['id'] }}</td>
                    <td style="font-weight: 600; max-width: 300px;">{{ Str::limit($post['title'], 50) }}</td>
                    <td style="font-size: 11px; color: #94a3b8;">/blog/{{ Str::limit($post['slug'], 35) }}</td>
                    <td>{{ number_format($post['views']) }}</td>
                    <td><span style="color: #ef4444; font-weight: 700;">0 link</span></td>
                    <td><a href="{{ route('admin.blog.edit', $post['id']) }}" class="btn-sm btn-primary">✏️ Thêm link</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state"><div class="icon">🎉</div><h3>Tuyệt vời!</h3><p>Tất cả bài viết đều có internal link trỏ đến.</p></div>
        @endif
    </div>

    {{-- Tab: Keyword Cannibalization --}}
    <div class="tab-panel" id="tab-cannibalization">
        @if(count($cannibalization) > 0)
        <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <strong>⚔️ {{ count($cannibalization) }} từ khóa bị trùng</strong> — nhiều bài dùng cùng Focus Keyword sẽ cạnh tranh lẫn nhau trên Google. Nên chọn keyword riêng biệt cho mỗi bài.
        </div>
        @foreach($cannibalization as $group)
        <div class="cannib-group">
            <div class="cannib-keyword">
                🎯 "{{ $group['keyword'] }}" <span class="cannib-count">{{ $group['count'] }} bài viết</span>
            </div>
            @foreach($group['posts'] as $post)
            <div class="cannib-post">
                <span style="font-size: 12px; color: #94a3b8;">ID {{ $post['id'] }}</span>
                <span style="flex: 1; font-weight: 500;">{{ Str::limit($post['title'], 60) }}</span>
                <span style="font-size: 12px; color: #94a3b8;">{{ number_format($post['views']) }} views</span>
                <a href="{{ route('admin.blog.edit', $post['id']) }}" class="btn-sm btn-primary">✏️ Đổi KW</a>
            </div>
            @endforeach
        </div>
        @endforeach
        @else
        <div class="empty-state"><div class="icon">🎉</div><h3>Tuyệt vời!</h3><p>Không có keyword nào bị trùng.</p></div>
        @endif
    </div>

    {{-- Tab: Bulk Editor --}}
    <div class="tab-panel" id="tab-bulk">
        <div style="background: #eff6ff; border: 1px solid #93c5fd; border-radius: 12px; padding: 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
            <div><strong>✏️ Sửa hàng loạt</strong> Meta Title, Meta Description và Focus Keyword cho nhiều bài cùng lúc.</div>
            <button class="btn-sm btn-success" onclick="saveBulkEdits()" id="bulk-save-btn">💾 Lưu tất cả</button>
        </div>
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow-x: auto;">
            <table class="seo-table" id="bulk-table">
                <thead><tr>
                    <th style="width:30px"><input type="checkbox" id="bulk-check-all"></th>
                    <th>Bài viết</th>
                    <th style="min-width:220px">Meta Title</th>
                    <th style="min-width:280px">Meta Description</th>
                    <th style="min-width:150px">Focus Keyword</th>
                </tr></thead>
                <tbody>
                @foreach($posts as $post)
                <tr data-id="{{ $post->id }}">
                    <td><input type="checkbox" class="bulk-check" value="{{ $post->id }}"></td>
                    <td>
                        <div style="font-weight: 600; font-size: 13px;">{{ Str::limit($post->title, 45) }}</div>
                        <div style="font-size: 11px; color: #94a3b8;">/blog/{{ Str::limit($post->slug, 30) }}</div>
                    </td>
                    <td><input type="text" class="bulk-input bulk-meta-title" value="{{ $post->meta_title ?? '' }}" placeholder="Meta Title..."></td>
                    <td><input type="text" class="bulk-input bulk-meta-desc" value="{{ $post->meta_description ?? '' }}" placeholder="Meta Description..."></td>
                    <td><input type="text" class="bulk-input bulk-keyword" value="{{ $post->focus_keyword ?? '' }}" placeholder="Focus Keyword..."></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Content Decay (AJAX) --}}
    <div class="tab-panel" id="tab-decay">
        <div id="decay-loading" style="text-align: center; padding: 40px;">
            <div style="font-size: 36px; margin-bottom: 12px;">⏳</div>
            <p style="color: #64748b;">Click tab để tải dữ liệu Content Decay...</p>
        </div>
        <div id="decay-content" style="display: none;"></div>
    </div>

    {{-- Tab: Broken Links (AJAX) --}}
    <div class="tab-panel" id="tab-broken">
        <div id="broken-loading" style="text-align: center; padding: 40px;">
            <div style="font-size: 36px; margin-bottom: 12px;">⏳</div>
            <p style="color: #64748b;">Click tab để tải dữ liệu Broken Links...</p>
        </div>
        <div id="broken-content" style="display: none;"></div>
    </div>

    {{-- Tab: Topical Authority (AJAX) --}}
    <div class="tab-panel" id="tab-authority">
        <div id="authority-loading" style="text-align: center; padding: 40px;">
            <div style="font-size: 36px; margin-bottom: 12px;">⏳</div>
            <p style="color: #64748b;">Click tab để tải dữ liệu Topical Authority Map...</p>
        </div>
        <div id="authority-content" style="display: none;"></div>
    </div>

    {{-- Tab: Recently Added --}}
    <div class="tab-panel" id="tab-recent">
        @if($recentlyAdded->count() > 0)
        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <strong>🆕 {{ $recentlyAdded->count() }} bài viết mới nhất</strong> (sắp xếp theo ID giảm dần — bao gồm cả bài import từ SQL).
        </div>
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
            <table class="seo-table">
                <thead><tr>
                    <th>#</th><th>Bài viết</th><th>Danh mục</th><th>Focus Keyword</th><th>Meta Title</th><th>Meta Desc</th><th>Ảnh</th><th>Ngày thêm</th><th>Hành động</th>
                </tr></thead>
                <tbody>
                @foreach($recentlyAdded as $post)
                <tr>
                    <td>{{ $post->id }}</td>
                    <td>
                        <div style="font-weight: 600; max-width: 280px;">{{ Str::limit($post->title, 50) }}</div>
                        <div style="font-size: 11px; color: #94a3b8;">/blog/{{ Str::limit($post->slug, 35) }}</div>
                    </td>
                    <td><span style="font-size: 12px; background: #f1f5f9; padding: 2px 8px; border-radius: 6px;">{{ $post->category ?? '—' }}</span></td>
                    <td><span style="font-size: 12px; color: {{ !empty($post->focus_keyword) ? '#059669' : '#94a3b8' }};">{{ $post->focus_keyword ?: '— Chưa đặt' }}</span></td>
                    <td>{!! !empty($post->meta_title) ? '<span style="color:#22c55e">✓ '.mb_strlen($post->meta_title).' ký tự</span>' : '<span style="color:#ef4444">✗</span>' !!}</td>
                    <td>{!! !empty($post->meta_description) ? '<span style="color:#22c55e">✓ '.mb_strlen($post->meta_description).' ký tự</span>' : '<span style="color:#ef4444">✗</span>' !!}</td>
                    <td>{!! !empty($post->image) ? '<span style="color:#22c55e">✓</span>' : '<span style="color:#ef4444">✗</span>' !!}</td>
                    <td style="font-size: 12px; color: #64748b; white-space: nowrap;">{{ \Carbon\Carbon::parse($post->created_at)->format('d/m H:i') }}</td>
                    <td><a href="{{ route('admin.blog.edit', $post->id) }}" class="btn-sm btn-primary">✏️ Sửa</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state"><div class="icon">📭</div><h3>Không có bài mới</h3><p>Chưa có bài viết nào được thêm trong 3 ngày qua.</p></div>
        @endif
    </div>
</div>

<script>
// Tabs
document.querySelectorAll('.seo-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.seo-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    });
});

// Filter
function filterPosts(value) {
    window.location.href = '{{ route("admin.seo") }}?filter=' + value;
}

// Search
function searchPosts(query) {
    query = query.toLowerCase();
    document.querySelectorAll('.post-row').forEach(row => {
        const title = row.dataset.title;
        row.style.display = title.includes(query) ? '' : 'none';
    });
}

// Sort posts by dropdown
function sortPosts(value) {
    if (!value) return;
    const tbody = document.querySelector('#tab-overview .seo-table tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('.post-row'));
    
    rows.sort((a, b) => {
        if (value === 'score-asc') return parseInt(a.dataset.score) - parseInt(b.dataset.score);
        if (value === 'score-desc') return parseInt(b.dataset.score) - parseInt(a.dataset.score);
        if (value === 'id-asc') return parseInt(a.cells[0].textContent) - parseInt(b.cells[0].textContent);
        if (value === 'id-desc') return parseInt(b.cells[0].textContent) - parseInt(a.cells[0].textContent);
        return 0;
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Sort table by clicking column headers
let sortDir = {};
function sortTable(colIdx, type) {
    const tbody = document.querySelector('#tab-overview .seo-table tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('.post-row'));
    
    sortDir[colIdx] = !sortDir[colIdx]; // toggle asc/desc
    const dir = sortDir[colIdx] ? 1 : -1;
    
    rows.sort((a, b) => {
        let va = a.cells[colIdx]?.textContent?.trim() || '';
        let vb = b.cells[colIdx]?.textContent?.trim() || '';
        if (type === 'num') return (parseInt(va) - parseInt(vb)) * dir;
        return va.localeCompare(vb) * dir;
    });
    
    rows.forEach(row => tbody.appendChild(row));
    
    // Update sort icons
    document.querySelectorAll('.seo-table th.sortable').forEach(th => th.classList.remove('active'));
    const th = document.querySelectorAll('.seo-table thead th')[colIdx];
    if (th) { th.classList.add('active'); const icon = th.querySelector('.sort-icon'); if (icon) icon.textContent = dir === 1 ? '▲' : '▼'; }
}

// Bulk check all
document.getElementById('bulk-check-all')?.addEventListener('change', function() {
    document.querySelectorAll('.bulk-check').forEach(cb => cb.checked = this.checked);
});

// Bulk save
async function saveBulkEdits() {
    const btn = document.getElementById('bulk-save-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Đang lưu...';
    
    const items = [];
    document.querySelectorAll('#bulk-table tbody tr').forEach(row => {
        const cb = row.querySelector('.bulk-check');
        if (cb && cb.checked) {
            items.push({
                id: row.dataset.id,
                meta_title: row.querySelector('.bulk-meta-title').value,
                meta_description: row.querySelector('.bulk-meta-desc').value,
                focus_keyword: row.querySelector('.bulk-keyword').value,
            });
        }
    });
    
    if (items.length === 0) {
        alert('Hãy chọn ít nhất 1 bài viết!');
        btn.disabled = false;
        btn.textContent = '💾 Lưu tất cả';
        return;
    }
    
    try {
        const resp = await fetch('{{ route("admin.seo.bulk-save") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ items }),
        });
        const data = await resp.json();
        alert(data.message);
        if (data.success) location.reload();
    } catch (e) {
        alert('Có lỗi xảy ra!');
    }
    
    btn.disabled = false;
    btn.textContent = '💾 Lưu tất cả';
}

// =============================================
// VIP TOOLS — AJAX-loaded tabs
// =============================================

let decayLoaded = false, brokenLoaded = false, authorityLoaded = false;

// Content Decay
async function loadContentDecay() {
    if (decayLoaded) return;
    const el = document.getElementById('decay-loading');
    el.innerHTML = '<div style="font-size: 36px; margin-bottom: 12px;">⏳</div><p style="color: #64748b;">Đang phân tích content decay...</p>';
    try {
        const resp = await fetch('{{ route("admin.seo.content-decay") }}');
        const data = await resp.json();
        decayLoaded = true;
        
        let html = `<div style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <strong>📉 ${data.summary.decaying_count} bài đang suy giảm</strong> / ${data.summary.total_posts} tổng — ${data.summary.decay_percentage}% decay rate
        </div>`;
        
        if (data.decaying.length > 0) {
            html += `<div style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
                <table class="seo-table"><thead><tr>
                    <th>#</th><th>Bài viết</th><th>Views</th><th>Views/ngày</th><th>Chưa update</th><th>Lý do</th><th>Hành động</th>
                </tr></thead><tbody>`;
            data.decaying.forEach(p => {
                const badge = p.decay_score >= 4 ? 'critical' : 'high';
                html += `<tr>
                    <td>${p.id}</td>
                    <td style="font-weight:600; max-width:250px;">${p.title.substring(0,50)}</td>
                    <td>${p.views.toLocaleString()}</td>
                    <td>${p.views_per_day}</td>
                    <td><strong>${p.days_since_update} ngày</strong></td>
                    <td style="font-size:12px;">${p.reasons.join('<br>')}</td>
                    <td><a href="/admin/blog/${p.id}/edit" class="btn-sm btn-primary">✏️ Refresh</a></td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        } else {
            html += '<div style="text-align:center;padding:40px;color:#94a3b8;"><div style="font-size:48px;margin-bottom:12px;">🎉</div><h3>Tuyệt vời!</h3><p>Không có bài nào đang suy giảm.</p></div>';
        }
        
        document.getElementById('decay-content').innerHTML = html;
        document.getElementById('decay-content').style.display = 'block';
        el.style.display = 'none';
    } catch (e) {
        el.innerHTML = '<p style="color: #ef4444;">Lỗi: ' + e.message + '</p>';
    }
}

// Broken Links
async function loadBrokenLinks() {
    if (brokenLoaded) return;
    const el = document.getElementById('broken-loading');
    el.innerHTML = '<div style="font-size: 36px; margin-bottom: 12px;">⏳</div><p style="color: #64748b;">Đang quét link hỏng... (có thể mất 30-60s)</p>';
    try {
        const resp = await fetch('{{ route("admin.seo.broken-links") }}');
        const data = await resp.json();
        brokenLoaded = true;
        
        let html = `<div style="background: ${data.broken_count > 0 ? '#fef2f2; border: 1px solid #fca5a5;' : '#f0fdf4; border: 1px solid #86efac;'} border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <strong>🔗 Đã quét ${data.checked}/${data.total_links} links</strong> — Tìm thấy <strong style="color: ${data.broken_count > 0 ? '#dc2626' : '#16a34a'};">${data.broken_count} link hỏng</strong>
        </div>`;
        
        if (data.broken.length > 0) {
            html += `<div style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
                <table class="seo-table"><thead><tr>
                    <th>URL hỏng</th><th>Status</th><th>Loại</th><th>Trong bài</th>
                </tr></thead><tbody>`;
            data.broken.forEach(link => {
                html += `<tr>
                    <td style="max-width:300px;word-break:break-all;font-size:12px;">${link.url}</td>
                    <td><span class="urgency-badge critical">${link.status}</span></td>
                    <td>${link.type === 'internal' ? '🏠 Internal' : '🌐 External'}</td>
                    <td style="font-size:12px;">${link.found_in.map(p => '<a href="/admin/blog/'+p.post_id+'/edit">'+p.post_title.substring(0,30)+'...</a>').join('<br>')}</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        } else {
            html += '<div style="text-align:center;padding:40px;color:#94a3b8;"><div style="font-size:48px;margin-bottom:12px;">🎉</div><h3>Tuyệt vời!</h3><p>Không tìm thấy link hỏng nào!</p></div>';
        }
        
        document.getElementById('broken-content').innerHTML = html;
        document.getElementById('broken-content').style.display = 'block';
        el.style.display = 'none';
    } catch (e) {
        el.innerHTML = '<p style="color: #ef4444;">Lỗi: ' + e.message + '</p>';
    }
}

// Topical Authority Map
async function loadTopicalAuthority() {
    if (authorityLoaded) return;
    const el = document.getElementById('authority-loading');
    el.innerHTML = '<div style="font-size: 36px; margin-bottom: 12px;">⏳</div><p style="color: #64748b;">Đang phân tích topical authority...</p>';
    try {
        const resp = await fetch('{{ route("admin.seo.topical-authority") }}');
        const data = await resp.json();
        authorityLoaded = true;
        
        let html = '';
        // Recommendations
        if (data.recommendations && data.recommendations.length > 0) {
            html += '<div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:12px;padding:16px;margin-bottom:16px;">';
            data.recommendations.forEach(r => { html += '<div style="margin-bottom:4px;">' + r + '</div>'; });
            html += '</div>';
        }
        
        // Topic cards
        html += '<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">';
        data.topics.forEach(topic => {
            const scoreClass = topic.authority_score >= 70 ? 'good' : (topic.authority_score >= 40 ? 'warning' : 'bad');
            html += `<div style="background:#fff; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="font-size:16px; font-weight:700;">${topic.name}</div>
                    <span class="score-badge ${scoreClass}" style="width:40px;height:40px;font-size:14px;">${topic.authority_score}</span>
                </div>
                <div style="font-size:13px;color:#64748b;margin-bottom:8px;">${topic.authority_level}</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px; font-size:12px; margin-bottom:12px;">
                    <div>📝 ${topic.post_count} bài</div>
                    <div>👁️ ${topic.total_views.toLocaleString()} views</div>
                    <div>📖 ${topic.avg_words} từ TB</div>
                    <div>📌 ${topic.cornerstone_count} cornerstone</div>
                </div>
                <div style="font-size:11px;border-top:1px solid #f1f5f9;padding-top:8px;">
                    ${topic.posts.slice(0,3).map(p => '• ' + p.title.substring(0,40) + ' (' + p.views + ' views)').join('<br>')}
                </div>
            </div>`;
        });
        html += '</div>';
        
        // Gaps
        if (data.gaps && data.gaps.length > 0) {
            html += '<div style="margin-top:20px;"><h3 style="font-size:16px;font-weight:700;margin-bottom:12px;">🔴 Content Gaps — Chủ đề cần bổ sung</h3>';
            data.gaps.forEach(gap => {
                html += `<div style="background:#fef2f2;border-radius:8px;padding:12px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;">
                    <div><strong>${gap.topic}</strong> (${gap.current_posts} bài) — ${gap.suggestion}</div>
                </div>`;
            });
            html += '</div>';
        }
        
        document.getElementById('authority-content').innerHTML = html;
        document.getElementById('authority-content').style.display = 'block';
        el.style.display = 'none';
    } catch (e) {
        el.innerHTML = '<p style="color: #ef4444;">Lỗi: ' + e.message + '</p>';
    }
}

// Auto-Fix V2
// Auto-Fix V2: Preview first, then confirm
let autoFixPreviewData = null;

async function previewAutoFixV2() {
    const btn = document.getElementById('autofix-btn');
    btn.disabled = true;
    btn.innerHTML = '⏳ Đang phân tích...';
    
    try {
        const resp = await fetch('{{ route("admin.seo.auto-fix-v2-preview") }}');
        const data = await resp.json();
        autoFixPreviewData = data;
        
        // Build preview modal
        let html = '<div id="autofix-modal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;">';
        html += '<div style="background:#fff;border-radius:16px;max-width:900px;width:100%;max-height:85vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">';
        
        // Header
        html += '<div style="padding:24px;border-bottom:1px solid #e5e7eb;position:sticky;top:0;background:#fff;border-radius:16px 16px 0 0;z-index:1;">';
        html += '<h2 style="margin:0;font-size:1.3rem;">🔍 Preview Auto-Fix V2</h2>';
        html += `<p style="color:#64748b;margin:8px 0 0;font-size:14px;">Sẽ sửa <strong>${data.fixed?.length || 0}</strong> bài · Đã tốt <strong>${data.already_good_count || 0}</strong> bài · Không sửa được <strong>${data.cannot_fix?.length || 0}</strong> bài</p>`;
        html += '</div>';
        
        // Body
        html += '<div style="padding:24px;">';
        
        if (data.fixed && data.fixed.length > 0) {
            data.fixed.forEach(post => {
                html += `<div style="border:1px solid #e5e7eb;border-radius:12px;margin-bottom:16px;overflow:hidden;">`;
                html += `<div style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:14px;">📝 ${post.title}</div>`;
                post.fixes.forEach(fix => {
                    html += '<div style="padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">';
                    html += `<div style="font-weight:600;color:#2563eb;margin-bottom:4px;">🔧 ${fix.issue || fix.type}</div>`;
                    if (fix.before) html += `<div style="color:#dc2626;background:#fef2f2;padding:6px 10px;border-radius:6px;margin:4px 0;word-break:break-all;"><s>${String(fix.before).substring(0,150)}</s></div>`;
                    if (fix.after) html += `<div style="color:#16a34a;background:#f0fdf4;padding:6px 10px;border-radius:6px;margin:4px 0;word-break:break-all;">✅ ${String(fix.after).substring(0,150)}</div>`;
                    html += '</div>';
                });
                html += '</div>';
            });
        } else {
            html += '<div style="text-align:center;padding:40px;color:#94a3b8;"><div style="font-size:48px;margin-bottom:12px;">🎉</div><h3>Tuyệt vời!</h3><p>Không có gì cần sửa. Tất cả bài viết đã tối ưu SEO.</p></div>';
        }
        
        // Cannot fix section
        if (data.cannot_fix && data.cannot_fix.length > 0) {
            html += '<div style="margin-top:24px;border-top:2px solid #e5e7eb;padding-top:20px;">';
            html += '<div onclick="document.getElementById(\'cannotfix-list\').style.display = document.getElementById(\'cannotfix-list\').style.display === \'none\' ? \'block\' : \'none\'" style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fef2f2;border-radius:12px;margin-bottom:12px;">';
            html += `<span style="font-weight:700;color:#dc2626;">⚠️ Không sửa được: ${data.cannot_fix.length} bài (bấm để xem)</span>`;
            html += '<span style="color:#94a3b8;">▼</span></div>';
            html += '<div id="cannotfix-list" style="display:none;">';
            data.cannot_fix.forEach(post => {
                html += `<div style="border:1px solid #fecaca;border-radius:8px;margin-bottom:8px;overflow:hidden;">`;
                html += `<div style="padding:10px 14px;background:#fff5f5;font-weight:600;font-size:13px;">⚠️ ${post.title}</div>`;
                post.issues.forEach(issue => {
                    html += `<div style="padding:8px 14px;font-size:12px;color:#92400e;border-top:1px solid #fef3c7;">• ${issue.reason || issue.type}</div>`;
                });
                html += '</div>';
            });
            html += '</div></div>';
        }
        
        html += '</div>';
        
        // Footer
        html += '<div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;gap:12px;justify-content:flex-end;position:sticky;bottom:0;background:#fff;border-radius:0 0 16px 16px;">';
        html += '<button onclick="closeAutoFixModal()" style="padding:10px 20px;border-radius:8px;border:1px solid #d1d5db;background:#fff;cursor:pointer;font-weight:600;">❌ Đóng</button>';
        if (data.fixed && data.fixed.length > 0) {
            html += '<button onclick="applyAutoFixV2()" id="apply-btn" style="padding:10px 20px;border-radius:8px;border:none;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;cursor:pointer;font-weight:600;box-shadow:0 4px 12px rgba(34,197,94,0.3);">✅ Áp dụng tất cả (' + data.fixed.length + ' bài)</button>';
        }
        html += '</div></div></div>';
        
        document.body.insertAdjacentHTML('beforeend', html);
    } catch (e) {
        alert('Lỗi: ' + e.message);
    }
    
    btn.disabled = false;
    btn.innerHTML = '🔍 Auto-Fix V2 Preview';
}

function closeAutoFixModal() {
    const modal = document.getElementById('autofix-modal');
    if (modal) modal.remove();
}

async function applyAutoFixV2() {
    if (!confirm('⚠️ Xác nhận áp dụng tất cả thay đổi?\n\nHành động này sẽ sửa nội dung trong database.')) return;
    
    const applyBtn = document.getElementById('apply-btn');
    applyBtn.disabled = true;
    applyBtn.innerHTML = '⏳ Đang áp dụng...';
    
    try {
        const resp = await fetch('{{ route("admin.seo.auto-fix-v2") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        });
        const data = await resp.json();
        
        closeAutoFixModal();
        alert(`✅ Đã áp dụng thành công!\n\nĐã sửa: ${data.fixed?.length || 0} bài\nĐã tốt: ${data.already_good_count || 0} bài`);
        location.reload();
    } catch (e) {
        alert('Lỗi: ' + e.message);
        applyBtn.disabled = false;
        applyBtn.innerHTML = '✅ Áp dụng tất cả';
    }
}
</script>
@endsection
