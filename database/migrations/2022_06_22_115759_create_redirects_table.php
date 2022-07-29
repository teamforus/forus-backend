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
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2000)->nullable();
            $table->string('target', 1000)->nullable();
            $table->string('client_type', 100)->nullable();
            $table->unsignedInteger('implementation_id')->nullable();
            $table->morphs('redirectable');
            $table->timestamps();

            $table->foreign('implementation_id')
                ->references('id')->on('implementations')
                ->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
