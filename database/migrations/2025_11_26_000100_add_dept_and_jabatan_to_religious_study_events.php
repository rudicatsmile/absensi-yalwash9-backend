<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('religious_study_events', function (Blueprint $table) {
            $table->unsignedBigInteger('departemen_id')->nullable()->after('notified');
            $table->unsignedBigInteger('jabatan_id')->nullable()->after('departemen_id');
            $table->index('departemen_id');
            $table->index('jabatan_id');
            $table->foreign('departemen_id')->references('id')->on('departemens')->onDelete('set null');
            $table->foreign('jabatan_id')->references('id')->on('jabatans')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('religious_study_events', function (Blueprint $table) {
            $table->dropForeign(['departemen_id']);
            $table->dropForeign(['jabatan_id']);
            $table->dropIndex(['departemen_id']);
            $table->dropIndex(['jabatan_id']);
            $table->dropColumn(['departemen_id', 'jabatan_id']);
        });
    }
};

