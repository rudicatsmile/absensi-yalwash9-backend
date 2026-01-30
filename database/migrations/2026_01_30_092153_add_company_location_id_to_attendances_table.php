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
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'company_location_id')) {
                $table->foreignId('company_location_id')->nullable()->constrained('company_locations')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
             if (Schema::hasColumn('attendances', 'company_location_id')) {
                $table->dropForeign(['company_location_id']);
                $table->dropColumn('company_location_id');
            }
        });
    }
};
