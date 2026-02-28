<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuthController;
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
    
    // Reports & System
    Route::get('/reports', [AdminController::class, 'revenueReports'])->name('admin.reports');
    Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::get('/logs', [AdminController::class, 'activityLogs'])->name('admin.logs');
});
