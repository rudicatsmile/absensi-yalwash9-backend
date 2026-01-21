<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add the new index first so the FK on user_id has an index to use
        Schema::table('employee_work_schedule', function (Blueprint $table) {
            // Add new unique index including shift_id
            $table->unique(['user_id', 'month', 'year', 'shift_id'], 'uniq_user_month_year_shift');
        });

        // 2. Drop the old index
        Schema::table('employee_work_schedule', function (Blueprint $table) {
            $table->dropUnique('uniq_user_month_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restore the old index
        Schema::table('employee_work_schedule', function (Blueprint $table) {
            $table->unique(['user_id', 'month', 'year'], 'uniq_user_month_year');
        });

        // 2. Drop the new index
        Schema::table('employee_work_schedule', function (Blueprint $table) {
            $table->dropUnique('uniq_user_month_year_shift');
        });
    }
};
