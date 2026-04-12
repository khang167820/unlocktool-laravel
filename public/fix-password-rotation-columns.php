<?php
// ERSP Script - Run once on production then DELETE this file!
// URL: https://unlocktool.us/fix-password-rotation-columns.php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$results = [];

$cols = [
    'needs_password_sync' => "TINYINT(1) NOT NULL DEFAULT 0",
    'new_password'        => "VARCHAR(255) NULL",
    'password_synced_at'  => "TIMESTAMP NULL",
];

// password_changed already exists from previous migration

foreach ($cols as $col => $def) {
    if (!Schema::hasColumn('accounts', $col)) {
        DB::statement("ALTER TABLE accounts ADD COLUMN {$col} {$def}");
        $results[] = "✅ Added: {$col}";
    } else {
        $results[] = "⏭️ Exists: {$col}";
    }
}

// Mark existing available accounts as synced
$marked = DB::table('accounts')->where('is_available', 1)->update([
    'password_changed' => 1,
    'needs_password_sync' => 0,
    'new_password' => null,
]);
$results[] = "✅ Marked {$marked} existing available accounts as synced";

header('Content-Type: application/json');
echo json_encode(['status' => 'SUCCESS', 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
