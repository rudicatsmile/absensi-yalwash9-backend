<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_work_time_schedule', function (Blueprint $table) {
            // Drop existing foreign key and index
            $table->dropForeign(['employee_id']);
            $table->dropIndex(['employee_id', 'schedule_date']);

            // Rename column
            $table->renameColumn('employee_id', 'user_id');

            // Add new foreign key for user_id
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Add shift_id column
            $table->foreignId('shift_id')->nullable()->after('user_id')->constrained('shift_kerjas')->nullOnDelete();

            // Add index for shift_id
            $table->index('shift_id');

            // Add new index for user_id and schedule_date
            $table->index(['user_id', 'schedule_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_work_time_schedule', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex(['shift_id']);
            $table->dropColumn('shift_id');

            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'schedule_date']);

            $table->renameColumn('user_id', 'employee_id');

            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['employee_id', 'schedule_date']);
        });
    }
};
