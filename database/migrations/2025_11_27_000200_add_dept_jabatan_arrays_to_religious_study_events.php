<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('religious_study_events', function (Blueprint $table) {
            // Simpan pilihan multiple departemen & jabatan dalam bentuk JSON array (opsional)
            $table->json('departemen_ids')->nullable()->after('jabatan_id');
            $table->json('jabatan_ids')->nullable()->after('departemen_ids');
        });
    }

    public function down(): void
    {
        Schema::table('religious_study_events', function (Blueprint $table) {
            $table->dropColumn(['departemen_ids', 'jabatan_ids']);
        });
    }
};

