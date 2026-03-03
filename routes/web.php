<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\SeoController;
use Illuminate\Support\Facades\Route;

// === Public Routes ===

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Checkout flow
Route::post('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/create-order', [CheckoutController::class, 'create'])->name('checkout.create');

// Order status
Route::get('/order-status', [OrderController::class, 'status'])->name('order.status');

// AJAX: Check payment status
Route::get('/api/check-payment/{code}', [OrderController::class, 'checkPayment'])->name('order.check');

// Pay2S Webhook (no CSRF)
Route::post('/webhook/pay2s', [WebhookController::class, 'handlePay2s'])->name('webhook.pay2s');

// === Blog Routes ===
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{category}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{slug}/rate', [BlogController::class, 'ratePost'])->name('blog.rate');

// === Sitemap ===
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemap-posts.xml', [SitemapController::class, 'posts']);

// === 301 Redirect: Old articles → New blog ===
Route::get('/articles/{filename}', function ($filename) {
    // Extract slug from filename: "14-thue-unlocktool-gia-re.php" → "thue-unlocktool-gia-re"
    $slug = preg_replace('/\.php$/', '', $filename);
    $slug = preg_replace('/^\d+-/', '', $slug);
    $slug = \Illuminate\Support\Str::slug($slug);
    
    // Check if blog post exists
    $exists = \App\Models\BlogPost::where('slug', $slug)->exists();
    if ($exists) {
        return redirect("/blog/{$slug}", 301);
    }
    
    // Fallback: redirect to blog listing
    return redirect('/blog', 301);
});

// === Cron: Reclaim expired accounts ===
Route::get('/cron/reclaim-accounts', function () {
    $secret = request('key');
    if ($secret !== config('app.cron_key', 'unlocktool-cron-2026')) {
        abort(403);
    }
    $count = \App\Services\AccountAllocationService::reclaimExpiredAccounts();
    return response()->json(['reclaimed' => $count]);
});

// === One-time: Delete 'index' blog post ===
Route::get('/cleanup/delete-index-post', function () {
    $deleted = \App\Models\BlogPost::where('slug', 'index')->delete();
    return "Deleted {$deleted} 'index' post(s). Remove this route after use.";
});

// === Admin Auth ===
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// === Admin Panel (Protected) ===
Route::prefix('admin')->middleware('admin')->group(function () {
    // Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // Orders
    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::post('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.status');
    
    // Accounts
    Route::get('/accounts', [AdminController::class, 'accounts'])->name('admin.accounts');
    Route::post('/accounts/add', [AdminController::class, 'addAccount'])->name('admin.accounts.add');
    Route::post('/accounts/{id}/toggle', [AdminController::class, 'toggleAccount'])->name('admin.accounts.toggle');
    Route::post('/accounts/{id}/update', [AdminController::class, 'updateAccount'])->name('admin.accounts.update');
    Route::delete('/accounts/{id}', [AdminController::class, 'deleteAccount'])->name('admin.accounts.delete');
    Route::get('/accounts/{id}/edit', [AdminController::class, 'editAccount'])->name('admin.accounts.edit');
    Route::post('/accounts/batch', [AdminController::class, 'batchToggleAccounts'])->name('admin.accounts.batch');
    
    // Prices
    Route::get('/prices', [AdminController::class, 'prices'])->name('admin.prices');
    Route::post('/prices/save/{id?}', [AdminController::class, 'savePrice'])->name('admin.prices.save');
    Route::delete('/prices/{id}', [AdminController::class, 'deletePrice'])->name('admin.prices.delete');
    
    // Blog
    Route::get('/blog', [AdminController::class, 'blog'])->name('admin.blog');
    Route::get('/blog/create', [AdminController::class, 'blogEdit'])->name('admin.blog.create');
    Route::get('/blog/{id}/edit', [AdminController::class, 'blogEdit'])->name('admin.blog.edit');
    Route::post('/blog/save/{id?}', [AdminController::class, 'blogSave'])->name('admin.blog.save');
    Route::delete('/blog/{id}', [AdminController::class, 'blogDelete'])->name('admin.blog.delete');
    Route::post('/blog/{id}/toggle', [AdminController::class, 'blogToggle'])->name('admin.blog.toggle');
    
    // SEO Analyzer (Full Suite)
    Route::get('/seo-analyzer', [SeoController::class, 'dashboard'])->name('admin.seo');
    Route::post('/seo-analyzer/analyze', [SeoController::class, 'analyzePost'])->name('admin.seo.analyze');
    Route::post('/seo-analyzer/bulk-save', [SeoController::class, 'bulkSave'])->name('admin.seo.bulk-save');
    Route::get('/seo-analyzer/redirects', [SeoController::class, 'redirects'])->name('admin.seo.redirects');
    Route::post('/seo-analyzer/redirects', [SeoController::class, 'redirectStore'])->name('admin.seo.redirect-store');
    Route::delete('/seo-analyzer/redirects/{id}', [SeoController::class, 'redirectDelete'])->name('admin.seo.redirect-delete');
    Route::post('/seo-analyzer/internal-links', [SeoController::class, 'internalLinks'])->name('admin.seo.internal-links');
    Route::get('/seo-analyzer/export-keywords', [SeoController::class, 'exportKeywords'])->name('admin.seo.export-keywords');
    Route::get('/seo-analyzer/content-decay', [SeoController::class, 'contentDecay'])->name('admin.seo.content-decay');
    Route::get('/seo-analyzer/broken-links', [SeoController::class, 'brokenLinks'])->name('admin.seo.broken-links');
    Route::get('/seo-analyzer/topical-authority', [SeoController::class, 'topicalAuthority'])->name('admin.seo.topical-authority');
    Route::get('/seo-analyzer/auto-fix-v2-preview', [SeoController::class, 'autoFixV2Preview'])->name('admin.seo.auto-fix-v2-preview');
    Route::post('/seo-analyzer/auto-fix-v2', [SeoController::class, 'autoFixV2'])->name('admin.seo.auto-fix-v2');
    
    // Reports & System
    Route::get('/reports', [AdminController::class, 'revenueReports'])->name('admin.reports');
    Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::get('/logs', [AdminController::class, 'activityLogs'])->name('admin.logs');
    
    // Backup
    Route::get('/backup', [AdminController::class, 'backupPage'])->name('admin.backup');
    Route::post('/backup/create', [AdminController::class, 'createBackup'])->name('admin.backup.create');
    Route::get('/backup/download/{filename}', [AdminController::class, 'downloadBackup'])->name('admin.backup.download');
    Route::delete('/backup/{filename}', [AdminController::class, 'deleteBackup'])->name('admin.backup.delete');
    
    // Coupons
    Route::get('/coupons', [AdminController::class, 'coupons'])->name('admin.coupons');
    Route::post('/coupons/{id?}', [AdminController::class, 'saveCoupon'])->name('admin.coupons.save');
    Route::post('/coupons/{id}/toggle', [AdminController::class, 'toggleCoupon'])->name('admin.coupons.toggle');
    
    // Export & Import
    Route::get('/export', [AdminController::class, 'exportPage'])->name('admin.export');
    Route::post('/export/orders', [AdminController::class, 'exportOrders'])->name('admin.export.orders');
    Route::post('/export/accounts', [AdminController::class, 'exportAccounts'])->name('admin.export.accounts');
    Route::post('/import/accounts', [AdminController::class, 'importAccounts'])->name('admin.import.accounts');
    
    // Global Search
    Route::get('/search', [AdminController::class, 'globalSearch'])->name('admin.search');
    
    // System Info
    Route::get('/system', [AdminController::class, 'systemInfo'])->name('admin.system');
    Route::post('/system/clear-cache', [AdminController::class, 'clearCache'])->name('admin.system.clear-cache');
    Route::post('/system/clear-views', [AdminController::class, 'clearViews'])->name('admin.system.clear-views');
    Route::post('/system/optimize', [AdminController::class, 'optimizeTables'])->name('admin.system.optimize');
    Route::get('/system/phpinfo', [AdminController::class, 'phpInfo'])->name('admin.system.phpinfo');
    
    // Underpaid Orders
    Route::get('/underpaid', [AdminController::class, 'underpaidOrders'])->name('admin.underpaid');
});
