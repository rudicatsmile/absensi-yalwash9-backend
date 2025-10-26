<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->unsignedBigInteger('meeting_type_id')->nullable()->after('user_id');
            $table->index('meeting_type_id');
            $table->foreign('meeting_type_id')
                ->references('id')
                ->on('meeting_types')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropForeign(['meeting_type_id']);
            $table->dropIndex(['meeting_type_id']);
            $table->dropColumn('meeting_type_id');
        });
    }
};