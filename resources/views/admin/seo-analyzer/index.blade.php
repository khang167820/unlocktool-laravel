@extends('admin.layouts.app')
@section('title', 'SEO Analyzer')
@section('page-title', 'SEO Analyzer')

@section('content')
{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div class="stat-info">
            <div class="stat-label">Tổng bài viết</div>
            <div class="stat-value">{{ count($analyzedPosts) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon {{ $avgScore >= 70 ? 'green' : ($avgScore >= 40 ? 'orange' : 'red') }}">📈</div>
        <div class="stat-info">
            <div class="stat-label">Điểm SEO trung bình</div>
            <div class="stat-value" style="color: {{ $avgScore >= 70 ? '#16a34a' : ($avgScore >= 40 ? '#f97316' : '#ef4444') }};">{{ $avgScore }}%</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">⚠️</div>
        <div class="stat-info">
            <div class="stat-label">Cần cải thiện (&lt;60%)</div>
            <div class="stat-value" style="color: #ef4444;">{{ $needsWork }}</div>
        </div>
    </div>
</div>

{{-- Legend --}}
<div style="margin: 16px 0; display: flex; gap: 16px; font-size: 13px; color: #64748b;">
    <span>🟢 Tốt</span>
    <span>🟡 Tạm được</span>
    <span>🔴 Cần sửa</span>
</div>

{{-- Table --}}
<div class="admin-card" style="overflow-x: auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 30%;">Bài viết</th>
                <th style="width: 80px; text-align: center;">Điểm</th>
                <th>Title</th>
                <th>Desc</th>
                <th>Keyword</th>
                <th>OG Image</th>
                <th>Schema</th>
                <th>Nội dung</th>
                <th>Links</th>
                <th>Headings</th>
                <th style="width: 60px;"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($analyzedPosts as $post)
            <tr>
                <td style="max-width: 250px;">
                    <strong style="font-size: 13px;">{{ Str::limit($post->title, 45) }}</strong>
                    <div style="font-size: 11px; color: var(--text-dimmed);">/blog/{{ $post->slug }}</div>
                </td>
                <td style="text-align: center;">
                    @php
                        $scoreColor = $post->score >= 70 ? '#16a34a' : ($post->score >= 40 ? '#f97316' : '#ef4444');
                        $scoreBg = $post->score >= 70 ? '#dcfce7' : ($post->score >= 40 ? '#fff7ed' : '#fef2f2');
                    @endphp
                    <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: {{ $scoreBg }}; border: 3px solid {{ $scoreColor }}; font-weight: 800; font-size: 14px; color: {{ $scoreColor }};">
                        {{ $post->score }}
                    </div>
                </td>
                @foreach(['title', 'description', 'keyword', 'og_image', 'schema', 'content_length', 'internal_links', 'headings'] as $key)
                    @php $c = $post->checks[$key] ?? null; @endphp
                    <td style="text-align: center;" title="{{ $c['msg'] ?? '' }}">
                        @if($c)
                            @if($c['status'] === 'good')
                                <span style="color: #16a34a; font-size: 16px;">✅</span>
                            @elseif($c['status'] === 'ok')
                                <span style="color: #f97316; font-size: 16px;">🟡</span>
                            @else
                                <span style="color: #ef4444; font-size: 16px;">❌</span>
                            @endif
                            <div style="font-size: 10px; color: #94a3b8; margin-top: 2px; white-space: nowrap;">{{ Str::limit($c['msg'], 18) }}</div>
                        @else
                            <span style="color: #cbd5e1;">—</span>
                        @endif
                    </td>
                @endforeach
                <td>
                    <a href="{{ route('admin.blog.edit', $post->id) }}" class="btn btn-sm btn-primary" title="Chỉnh sửa">✏️</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Scoring Info --}}
<div class="admin-card" style="margin-top: 20px;">
    <div class="admin-card-title">📋 Tiêu chí chấm điểm SEO</div>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 13px; color: #475569;">
        <div><strong>Meta Title (15đ)</strong> — 50-60 ký tự là tối ưu</div>
        <div><strong>Meta Description (15đ)</strong> — 120-160 ký tự</div>
        <div><strong>Focus Keyword (15đ)</strong> — Xuất hiện trong title + desc + content</div>
        <div><strong>Nội dung (15đ)</strong> — Tối thiểu 1,500 ký tự</div>
        <div><strong>OG Image (10đ)</strong> — Có ảnh khi share MXH</div>
        <div><strong>Schema (10đ)</strong> — Có structured data</div>
        <div><strong>Internal Links (10đ)</strong> — Tối thiểu 3 link nội bộ</div>
        <div><strong>Headings (10đ)</strong> — Tối thiểu 3 thẻ H2/H3</div>
    </div>
</div>
@endsection
