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
        Schema::table('employee_work_schedule', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'month', 'year', 'shift_id'],
                'uniq_user_month_year_shift'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_work_schedule', function (Blueprint $table) {
            $table->dropUnique('uniq_user_month_year_shift');
        });
    }
};
