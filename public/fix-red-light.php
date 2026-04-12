<?php
// One-time fix: Reset all available accounts to password_changed=0 (red light)
// Run once then DELETE this file!

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$affected = DB::table('accounts')
    ->where('is_available', 1)
    ->where('password_changed', 1)
    ->update(['password_changed' => 0]);

header('Content-Type: application/json');
echo json_encode([
    'status' => 'SUCCESS',
    'message' => "Reset {$affected} accounts to red light (password_changed=0)",
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
