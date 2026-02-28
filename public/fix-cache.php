<?php
/**
 * fix-cache.php — Clear all Laravel caches via browser
 * Delete this file after use in production!
 * Access: https://test.unlocktool.us/fix-cache.php
 */

// Clear bootstrap cache
$cacheDir = __DIR__ . '/../bootstrap/cache';
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '/*.php') as $file) {
        @unlink($file);
    }
    echo "✅ bootstrap/cache cleaned<br>";
}

// Clear view cache
$viewsDir = __DIR__ . '/../storage/framework/views';
if (is_dir($viewsDir)) {
    foreach (glob($viewsDir . '/*.php') as $file) {
        @unlink($file);
    }
    echo "✅ views cache cleaned<br>";
}

// OPcache reset
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache reset<br>";
}

// Try artisan commands (may not work on shared hosting)
if (function_exists('shell_exec')) {
    @shell_exec('cd ' . dirname(__DIR__) . ' && php artisan optimize:clear 2>&1');
    echo "✅ artisan optimize:clear executed<br>";
} else {
    echo "⚠️ shell_exec disabled (shared hosting) — skipped artisan<br>";
}

echo "<br><strong>🎉 HOÀN TẤT!</strong>";
