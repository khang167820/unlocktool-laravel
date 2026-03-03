<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lệnh accounts:reclaim có sẵn để chạy thủ công: php artisan accounts:reclaim
// KHÔNG tự động chạy cron — admin tự quản lý bằng tay
