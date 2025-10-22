<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_location_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_location_id')->constrained('company_locations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_location_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_location_user');
    }
};