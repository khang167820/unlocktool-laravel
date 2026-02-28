<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
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
