<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_rotation_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->default('Agent Windows');
            $table->string('token_hash', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_rotation_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('service_type', 50)->default('Unlocktool');
            $table->string('status', 20)->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->text('last_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_rotation_jobs');
        Schema::dropIfExists('password_rotation_agents');
    }
};
