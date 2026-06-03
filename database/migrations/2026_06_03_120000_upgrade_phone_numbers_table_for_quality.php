<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->unsignedInteger('verification_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('fail_count')->default(0);
            $table->unsignedInteger('reputation_score')->default(100);
            $table->timestamp('last_used_at')->nullable();

            // Add indexes for optimization
            $table->index('status');
            $table->index('reputation_score');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['reputation_score']);
            $table->dropIndex(['last_used_at']);

            $table->dropColumn([
                'verification_count',
                'success_count',
                'fail_count',
                'reputation_score',
                'last_used_at'
            ]);
        });
    }
};
