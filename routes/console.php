<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động thu hồi tài khoản hết hạn mỗi 3 phút
Schedule::command('accounts:reclaim')->everyThreeMinutes();
