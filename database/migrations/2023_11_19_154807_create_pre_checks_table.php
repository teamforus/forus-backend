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
        Schema::create('pre_checks', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('default')->default(false);
            $table->unsignedInteger('implementation_id');
            $table->unsignedInteger('order')->nullable();
            $table->string('title', 50);
            $table->string('description', 1000)->nullable();
            $table->timestamps();

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
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
        Schema::dropIfExists('pre_checks');
    }
};
