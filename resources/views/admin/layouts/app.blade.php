<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - UnlockTool.us</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* Theme Variables */
    :root {
        --bg-primary: #0f172a;
        --bg-secondary: #1e293b;
        --bg-hover: #334155;
        --border-color: #334155;
        --text-primary: #f1f5f9;
        --text-secondary: #e2e8f0;
        --text-muted: #94a3b8;
        --text-dimmed: #64748b;
    }
    
    html.light-mode {
        --bg-primary: #f8fafc;
        --bg-secondary: #ffffff;
        --bg-hover: #f1f5f9;
        --border-color: #e2e8f0;
        --text-primary: #0f172a;
        --text-secondary: #1e293b;
        --text-muted: #475569;
        --text-dimmed: #64748b;
    }
    
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: var(--bg-primary); color: var(--text-secondary); min-height: 100vh; transition: background 0.3s, color 0.3s; }
    
    /* Layout */
    .admin-layout { display: flex; min-height: 100vh; }
    
    /* Sidebar */
    .admin-sidebar {
        width: 260px;
        background: var(--bg-secondary);
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 100;
        transition: background 0.3s, border-color 0.3s;
    }
    .admin-logo {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .admin-logo img { width: 40px; height: 40px; border-radius: 10px; }
    .admin-logo-text { font-size: 16px; font-weight: 700; color: var(--text-primary); }
    .admin-logo-sub { font-size: 11px; color: var(--text-dimmed); }
    
    .admin-nav { padding: 16px 12px; flex: 1; overflow-y: auto; }
    .admin-nav-section { margin-bottom: 24px; }
    .admin-nav-title { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--text-dimmed); padding: 0 12px; margin-bottom: 8px; }
    
    .admin-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 10px;
        text-decoration: none;
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
        margin-bottom: 4px;
    }
    .admin-nav-item:hover { background: var(--bg-hover); color: var(--text-primary); }
    .admin-nav-item.active { background: #3b82f6; color: #fff; }
    .admin-nav-item svg { width: 20px; height: 20px; }
    .admin-nav-badge { background: #ef4444; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 10px; margin-left: auto; }
    
    /* Main Content */
    .admin-main { flex: 1; margin-left: 260px; min-height: 100vh; }
    
    .admin-header {
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 50;
        transition: background 0.3s, border-color 0.3s;
    }
    .admin-header-title { font-size: 18px; font-weight: 700; color: var(--text-primary); }
    .admin-header-user {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .admin-header-user span { font-size: 13px; color: var(--text-muted); }
    .admin-header-user strong { color: var(--text-primary); }
    .admin-logout {
        padding: 8px 16px;
        background: #dc2626;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }
    
    /* Theme Toggle Button */
    .theme-toggle {
        padding: 8px 12px;
        background: var(--bg-hover);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--text-muted);
        transition: all 0.2s;
    }
    .theme-toggle:hover { background: var(--border-color); color: var(--text-primary); }
    .theme-toggle svg { width: 16px; height: 16px; }
    
    .admin-content { padding: 24px; }
    
    /* Cards */
    .admin-card {
        background: var(--bg-secondary);
        border-radius: 16px;
        padding: 20px;
        border: 1px solid var(--border-color);
        margin-bottom: 20px;
        transition: background 0.3s, border-color 0.3s;
    }
    .admin-card-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 16px;
    }
    
    /* Stats Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        transition: background 0.3s, border-color 0.3s;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .stat-icon.green { background: rgba(16, 185, 129, 0.15); }
    .stat-icon.blue { background: rgba(59, 130, 246, 0.15); }
    .stat-icon.orange { background: rgba(249, 115, 22, 0.15); }
    .stat-icon.purple { background: rgba(139, 92, 246, 0.15); }
    .stat-info { flex: 1; }
    .stat-label { font-size: 12px; color: var(--text-dimmed); margin-bottom: 4px; }
    .stat-value { font-size: 24px; font-weight: 700; color: var(--text-primary); }
    .stat-sub { font-size: 11px; color: var(--text-dimmed); margin-top: 4px; }
    
    /* Tables */
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th, .admin-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border-color); }
    .admin-table th { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-dimmed); background: var(--bg-primary); }
    .admin-table td { font-size: 13px; color: var(--text-secondary); }
    .admin-table tr:hover { background: var(--bg-hover); }
    
    /* Badges */
    .badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .badge-pending { background: #fef3c7; color: #d97706; }
    .badge-paid { background: #dbeafe; color: #2563eb; }
    .badge-completed { background: #dcfce7; color: #16a34a; }
    .badge-cancelled { background: #fee2e2; color: #dc2626; }
    .badge-active { background: #dcfce7; color: #16a34a; }
    .badge-inactive { background: #fee2e2; color: #dc2626; }
    .badge-published { background: #dcfce7; color: #16a34a; }
    .badge-draft { background: #fef3c7; color: #d97706; }
    
    /* Buttons */
    .btn { padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .btn-primary { background: #3b82f6; color: #fff; }
    .btn-primary:hover { background: #2563eb; }
    .btn-success { background: #10b981; color: #fff; }
    .btn-danger { background: #dc2626; color: #fff; }
    .btn-secondary { background: var(--bg-hover); color: var(--text-secondary); }
    .btn-sm { padding: 6px 12px; font-size: 11px; }
    
    /* Forms */
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
    .form-input, .form-select {
        width: 100%;
        padding: 10px 14px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-secondary);
        font-size: 13px;
        transition: background 0.3s, border-color 0.3s, color 0.3s;
    }
    .form-input:focus, .form-select:focus { outline: none; border-color: #3b82f6; }
    
    /* Filter Bar */
    .filter-bar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .filter-bar .form-input, .filter-bar .form-select { width: auto; min-width: 180px; }
    
    /* Pagination */
    .pagination { display: flex; gap: 4px; justify-content: center; margin-top: 20px; flex-wrap: wrap; align-items: center; }
    .pagination a, .pagination span { padding: 8px 14px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-muted); font-size: 12px; text-decoration: none; }
    .pagination a:hover { background: var(--bg-hover); color: var(--text-primary); }
    .pagination .active, .pagination .active span { background: #3b82f6; border-color: #3b82f6; color: #fff; }
    .pagination svg { width: 14px; height: 14px; display: inline-block; }
    .pagination nav { display: flex; align-items: center; gap: 8px; }
    .pagination nav > div { display: flex; align-items: center; gap: 8px; }
    .pagination nav > div:first-child { display: none; }
    .pagination nav > div:last-child { display: flex; align-items: center; }
    .pagination nav span[aria-disabled="true"] svg, 
    .pagination nav a svg { width: 16px; height: 16px; }
    
    /* Fix Laravel Tailwind Pagination */
    nav[role="navigation"] { display: flex; justify-content: center; align-items: center; gap: 4px; flex-wrap: wrap; }
    nav[role="navigation"] > div { display: flex; align-items: center; gap: 8px; }
    nav[role="navigation"] > div:first-child { font-size: 13px; color: var(--text-muted); }
    nav[role="navigation"] span[aria-current="page"] span { background: #3b82f6; color: #fff; padding: 8px 14px; border-radius: 6px; font-size: 12px; }
    nav[role="navigation"] a { padding: 8px 14px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-muted); font-size: 12px; text-decoration: none; }
    nav[role="navigation"] a:hover { background: var(--bg-hover); color: var(--text-primary); }
    nav[role="navigation"] span[aria-disabled="true"] { padding: 8px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-dimmed); font-size: 12px; cursor: not-allowed; }
    nav[role="navigation"] svg { width: 16px; height: 16px; }
    
    /* Alerts */
    .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; }
    .alert-success { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .alert-error, .alert-danger { background: rgba(220, 38, 38, 0.15); color: #ef4444; border: 1px solid rgba(220, 38, 38, 0.3); }
    
    /* Mobile hamburger button */
    .mobile-menu-toggle {
        display: none;
        background: var(--bg-hover);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 8px;
        cursor: pointer;
        color: var(--text-primary);
        align-items: center;
        justify-content: center;
    }
    .mobile-menu-toggle svg { width: 22px; height: 22px; }
    
    /* Sidebar overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 99;
        backdrop-filter: blur(2px);
    }
    .sidebar-overlay.active { display: block; }

    /* Mobile */
    @media (max-width: 768px) {
        .mobile-menu-toggle { display: flex; }
        
        .admin-sidebar {
            width: 280px;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .admin-sidebar.open { transform: translateX(0); }
        
        .admin-main { margin-left: 0; }
        
        .admin-header {
            padding: 12px 16px;
            gap: 8px;
        }
        .admin-header-title { font-size: 15px; white-space: nowrap; }
        
        /* Hide search bar on mobile */
        .admin-header > div:nth-child(2) { display: none; }
        
        /* Simplify header user area */
        .admin-header-user span { display: none; }
        .admin-header-user { gap: 8px; }
        .theme-toggle span { display: none; }
        
        .admin-content { padding: 16px 12px; }
        
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
        .stat-card { padding: 14px; gap: 10px; }
        .stat-icon { width: 40px; height: 40px; font-size: 20px; }
        .stat-value { font-size: 20px; }
        
        /* Tables scroll horizontally */
        .admin-card { padding: 12px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .admin-table { min-width: 600px; }
        .admin-table th, .admin-table td { padding: 10px 12px; font-size: 12px; white-space: nowrap; }
        
        /* Filter bar stacks */
        .filter-bar { flex-direction: column; gap: 8px; }
        .filter-bar .form-input, .filter-bar .form-select { width: 100%; min-width: unset; }
        
        /* Pagination */
        .pagination { gap: 2px; }
        .pagination a, .pagination span { padding: 6px 10px; font-size: 11px; }
        
        /* Buttons */
        .btn { padding: 8px 12px; font-size: 11px; }
    }
    
    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
        .admin-header-title { font-size: 14px; }
        .admin-logout { padding: 6px 10px; font-size: 11px; }
    }
    </style>
    <script>
    // Theme toggle functionality
    function toggleTheme() {
        const html = document.documentElement;
        const isLight = html.classList.toggle('light-mode');
        localStorage.setItem('admin-theme', isLight ? 'light' : 'dark');
        updateThemeIcon();
    }
    function updateThemeIcon() {
        const btn = document.getElementById('theme-btn');
        if (!btn) return;
        const isLight = document.documentElement.classList.contains('light-mode');
        btn.innerHTML = isLight 
            ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg> Tối'
            : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg> Sáng';
    }
    // Apply saved theme on load
    (function() {
        const saved = localStorage.getItem('admin-theme');
        if (saved === 'light') document.documentElement.classList.add('light-mode');
    })();
    document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <img src="/favicon.ico" alt="Logo">
                <div>
                    <div class="admin-logo-text">Admin Panel</div>
                    <div class="admin-logo-sub">UnlockTool.us</div>
                </div>
            </div>
            
            <nav class="admin-nav">
                <div class="admin-nav-section">
                    <div class="admin-nav-title">Tổng quan</div>
                    <a href="{{ route('admin.dashboard') }}" class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Bảng điều khiển
                    </a>
                </div>
                
                <div class="admin-nav-section">
                    <div class="admin-nav-title">Quản lý</div>
                    <a href="{{ route('admin.accounts') }}" class="admin-nav-item {{ request()->routeIs('admin.accounts*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Tài khoản
                    </a>
                    <a href="{{ route('admin.prices') }}" class="admin-nav-item {{ request()->routeIs('admin.prices') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                        Bảng giá
                    </a>
                    <a href="{{ route('admin.orders') }}" class="admin-nav-item {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                        Đơn hàng
                    </a>
                    <a href="{{ route('admin.coupons') }}" class="admin-nav-item {{ request()->routeIs('admin.coupons') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        Mã giảm giá
                    </a>
                </div>
                
                <div class="admin-nav-section">
                    <div class="admin-nav-title">Hệ thống</div>
                    <a href="{{ route('admin.underpaid') }}" class="admin-nav-item {{ request()->routeIs('admin.underpaid') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Thiếu tiền
                    </a>
                    <a href="{{ route('admin.blog') }}" class="admin-nav-item {{ request()->routeIs('admin.blog*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Blog
                    </a>
                    <a href="{{ route('admin.reports') }}" class="admin-nav-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        Báo cáo
                    </a>
                    <a href="{{ route('admin.settings') }}" class="admin-nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        Cài đặt
                    </a>
                    <a href="{{ route('admin.logs') }}" class="admin-nav-item {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Nhật ký
                    </a>
                </div>
                
                <div class="admin-nav-section">
                    <div class="admin-nav-title">Công cụ</div>
                    <a href="{{ route('admin.seo') }}" class="admin-nav-item {{ request()->routeIs('admin.seo') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/><path d="M8 11h6"/><path d="M11 8v6"/></svg>
                        SEO Analyzer
                    </a>
                    <a href="{{ route('admin.seo.redirects') }}" class="admin-nav-item {{ request()->routeIs('admin.seo.redirects') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/><path d="M15 18l-6-6 6-6"/></svg>
                        301 Redirects
                    </a>
                    <a href="{{ route('admin.export') }}" class="admin-nav-item {{ request()->routeIs('admin.export') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Export/Import
                    </a>
                    <a href="{{ route('admin.backup') }}" class="admin-nav-item {{ request()->routeIs('admin.backup*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Sao lưu
                    </a>
                    <a href="{{ route('admin.system') }}" class="admin-nav-item {{ request()->routeIs('admin.system*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Hệ thống
                    </a>
                </div>
                
                <div class="admin-nav-section">
                    <div class="admin-nav-title">Khác</div>
                    <a href="/" class="admin-nav-item" target="_blank">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        Xem website
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main -->
        <main class="admin-main">
            <header class="admin-header">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()" aria-label="Menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <h1 class="admin-header-title">@yield('page-title', 'Bảng điều khiển')</h1>
                
                <!-- Global Search -->
                <div style="flex: 1; max-width: 400px; margin: 0 24px;">
                    <form action="{{ route('admin.search') }}" method="GET" style="margin: 0;">
                        <div style="position: relative;">
                            <input type="text" name="q" placeholder="Tìm kiếm đơn hàng, tài khoản..." 
                                   value="{{ request('q') }}"
                                   style="width: 100%; padding: 10px 16px 10px 40px; border-radius: 10px; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); font-size: 13px;">
                            <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #64748b;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        </div>
                    </form>
                </div>
                
                <div class="admin-header-user">
                    <button id="theme-btn" class="theme-toggle" onclick="toggleTheme()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg> Sáng
                    </button>
                    <span>Xin chào, <strong>{{ session('admin_username', 'Admin') }}</strong></span>
                    <form action="{{ route('admin.logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="admin-logout">Đăng xuất</button>
                    </form>
                </div>
            </header>
            
            <div class="admin-content">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif
                
                @yield('content')
            </div>
        </main>
    </div>
<script>
function toggleSidebar() {
    document.querySelector('.admin-sidebar').classList.toggle('open');
    document.getElementById('sidebar-overlay').classList.toggle('active');
}
// Close sidebar on nav item click (mobile)
document.querySelectorAll('.admin-nav-item').forEach(item => {
    item.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            document.querySelector('.admin-sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('active');
        }
    });
});
</script>
</body>
</html>
