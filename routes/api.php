<?php

use App\Http\Controllers\Admin\PasswordRotationAgentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — unlocktool.us
|--------------------------------------------------------------------------
| Agent đổi pass tự động gọi các endpoint này qua Bearer token.
| Không cần CSRF, không cần session.
*/

Route::prefix('password-rotation-agent')->group(function () {
    Route::post('/poll', [PasswordRotationAgentController::class, 'poll']);
    Route::post('/jobs/{jobId}/report', [PasswordRotationAgentController::class, 'report']);
});
