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
        Schema::table('religious_study_events', function (Blueprint $table) {
            if (!Schema::hasColumn('religious_study_events', 'isoverlay')) {
                $table->boolean('isoverlay')->default(false)->after('cancelled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('religious_study_events', function (Blueprint $table) {
            if (Schema::hasColumn('religious_study_events', 'isoverlay')) {
                $table->dropColumn('isoverlay');
            }
        });
    }
};
