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
            $table->json('jam_kerja_ids')->nullable()->after('allowed_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_work_schedule', function (Blueprint $table) {
            $table->dropColumn('jam_kerja_ids');
        });
    }
};
