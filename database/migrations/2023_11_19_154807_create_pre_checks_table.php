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
            $table->string('title', 200);
            $table->string('title_short', 100);
            $table->string('description', 2000)->nullable();
            $table->timestamps();

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('restrict');
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
