<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('implementation_social_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('implementation_id');
            $table->enum('type', ['facebook', 'twitter', 'youtube']);
            $table->string('link', 100);
            $table->string('title', 100);
            $table->timestamps();
            $table->unique(['implementation_id', 'type']);

            $table->foreign('implementation_id')
                ->references('id')->on('implementations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('implementation_social_media');
    }
};
