<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('needs_password_sync')->default(false)->after('password_changed');
            $table->string('new_password', 255)->nullable()->after('needs_password_sync');
            $table->timestamp('password_synced_at')->nullable()->after('new_password');
        });

        // Mark existing available accounts as synced (avoid showing old accounts)
        DB::table('accounts')->where('is_available', 1)->update([
            'password_changed' => 1,
            'needs_password_sync' => 0,
            'new_password' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['needs_password_sync', 'new_password', 'password_synced_at']);
        });
    }
};
