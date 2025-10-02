<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('household_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->restrictOnDelete();
            $table->foreignId('profile_id')->constrained('profiles')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_profiles');
    }
};
