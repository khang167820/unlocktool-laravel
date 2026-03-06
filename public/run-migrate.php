<?php
// Direct migration runner - bypass Laravel routing
// DELETE THIS FILE AFTER USE!

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Check if column already exists
    if (Illuminate\Support\Facades\Schema::hasColumn('accounts', 'expires_at')) {
        echo json_encode(['status' => 'ok', 'message' => 'Column expires_at already exists!']);
        exit;
    }

    // Run migration manually 
    Illuminate\Support\Facades\Schema::table('accounts', function ($table) {
        $table->date('expires_at')->nullable()->after('note_date');
    });

    echo json_encode([
        'status' => 'ok', 
        'message' => 'Column expires_at added to accounts table!',
        'time' => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
