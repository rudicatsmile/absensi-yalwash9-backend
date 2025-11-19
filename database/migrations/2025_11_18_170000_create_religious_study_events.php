<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('religious_study_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('event_at');
            $table->dateTime('notify_at')->index();
            $table->string('location')->nullable();
            $table->string('theme')->nullable();
            $table->string('speaker')->nullable();
            $table->text('message')->nullable();
            $table->boolean('cancelled')->default(false);
            $table->boolean('notified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('religious_study_events');
    }
};