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
        if (!Schema::hasTable('jam_kerjas')) {
            Schema::create('jam_kerjas', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->time('start_time');
                $table->time('end_time');
                $table->boolean('is_cross_day')->default(false)->comment('Apakah shift melewati tengah malam');
                $table->integer('grace_period_minutes')->default(10)->comment('Toleransi keterlambatan dalam menit');
                $table->boolean('is_active')->default(true)->comment('Status aktif shift');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jam_kerjas');
    }
};
