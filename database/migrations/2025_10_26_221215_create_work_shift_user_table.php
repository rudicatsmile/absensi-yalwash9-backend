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
        Schema::create('work_shift_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_shift_id')
                  ->constrained('work_shifts')
                  ->onDelete('cascade')
                  ->comment('Foreign key ke tabel work_shifts');
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('Foreign key ke tabel users');
            $table->timestamps();

            // Create unique constraint to prevent duplicate assignments
            $table->unique(['work_shift_id', 'user_id'], 'unique_work_shift_user');

            // Create indexes for better performance
            $table->index('work_shift_id', 'idx_work_shift_user_work_shift_id');
            $table->index('user_id', 'idx_work_shift_user_user_id');
            $table->index('created_at', 'idx_work_shift_user_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_shift_user');
    }
};
