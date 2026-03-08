{{-- Breadcrumbs UI Navigation --}}
@if(isset($breadcrumbs) && count($breadcrumbs) > 0)
<nav aria-label="breadcrumb" class="site-breadcrumb">
    <ol>
        @foreach($breadcrumbs as $index => $crumb)
            @if($index < count($breadcrumbs) - 1)
                <li><a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a></li>
                <li class="sep">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </li>
            @else
                <li class="current">{{ $crumb['name'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
<style>
.site-breadcrumb { padding: 12px 0; font-size: 14px; }
.site-breadcrumb ol { list-style: none; display: flex; flex-wrap: wrap; align-items: center; gap: 4px; margin: 0; padding: 0; }
.site-breadcrumb li { display: flex; align-items: center; }
.site-breadcrumb a { color: var(--primary, #1e40af); text-decoration: none; font-weight: 500; transition: color 0.2s; }
.site-breadcrumb a:hover { color: var(--accent, #f97316); text-decoration: underline; }
.site-breadcrumb .sep { color: var(--muted, #94a3b8); opacity: 0.6; }
.site-breadcrumb .current { color: var(--muted, #64748b); font-weight: 400; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
[data-theme="dark"] .site-breadcrumb a { color: #93c5fd; }
[data-theme="dark"] .site-breadcrumb .current { color: #94a3b8; }
[data-theme="dark"] .site-breadcrumb .sep { color: #64748b; }
</style>
@endif
