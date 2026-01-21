<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_work_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('shift_kerjas')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->json('allowed_days');
            $table->timestamps();

            $table->unique(['user_id', 'month', 'year'], 'uniq_user_month_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_work_schedule');
    }
};
