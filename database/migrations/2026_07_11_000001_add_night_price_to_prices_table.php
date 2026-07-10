<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->integer('night_price')->nullable()->after('price');
            $table->string('night_start', 5)->nullable()->default('21:00')->after('night_price');
            $table->string('night_end', 5)->nullable()->default('09:00')->after('night_start');
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropColumn(['night_price', 'night_start', 'night_end']);
        });
    }
};
