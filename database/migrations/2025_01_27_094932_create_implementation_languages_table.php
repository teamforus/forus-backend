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
        Schema::create('implementation_languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('implementation_id');
            $table->unsignedInteger('language_id');
            $table->timestamps();

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('cascade');

            $table->foreign('language_id')
                ->references('id')
                ->on('languages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('implementation_languages', function (Blueprint $table) {
            $table->dropForeign(['implementation_id']);
            $table->dropForeign(['language_id']);
        });

        Schema::dropIfExists('implementation_languages');
    }
};
