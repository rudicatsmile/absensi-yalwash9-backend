<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('religious_study_events', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('message');
            $table->index('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('religious_study_events', function (Blueprint $table) {
            $table->dropIndex(['image_path']);
            $table->dropColumn('image_path');
        });
    }
};

