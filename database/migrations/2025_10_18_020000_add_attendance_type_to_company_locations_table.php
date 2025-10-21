<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->enum('attendance_type', [
                'location_based_only',
                'face_recognition_only',
                'hybrid',
            ])->default('location_based_only')->after('radius_km');
        });
    }

    public function down(): void
    {
        Schema::table('company_locations', function (Blueprint $table) {
            $table->dropColumn('attendance_type');
        });
    }
};