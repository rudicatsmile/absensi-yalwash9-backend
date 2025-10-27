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
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama shift kerja');
            $table->time('start_time')->comment('Waktu mulai shift');
            $table->time('end_time')->comment('Waktu selesai shift');
            $table->boolean('is_cross_day')->default(false)->comment('Flag untuk shift lintas hari');
            $table->integer('grace_period_minutes')->default(0)->comment('Masa toleransi dalam menit');
            $table->boolean('is_active')->default(true)->comment('Status aktif shift');
            $table->text('description')->nullable()->comment('Deskripsi shift');
            $table->timestamps();

            // Create indexes for better performance
            $table->index('name', 'idx_work_shifts_name');
            $table->index('is_active', 'idx_work_shifts_is_active');
            $table->index('start_time', 'idx_work_shifts_start_time');
            $table->index(['is_active', 'start_time'], 'idx_work_shifts_active_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_shifts');
    }
};
