<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'rental_expires_at')) {
                $table->timestamp('rental_expires_at')->nullable()->after('is_available');
            }
            if (!Schema::hasColumn('accounts', 'rental_order_code')) {
                $table->string('rental_order_code')->nullable()->after('rental_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['rental_expires_at', 'rental_order_code']);
        });
    }
};
