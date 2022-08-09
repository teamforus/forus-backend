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
        Schema::create('implementation_pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('implementation_id');
            $table->string('page_type', 50)->nullable();
            $table->text('content')->nullable();
            $table->string('external_url', 300)->nullable();
            $table->boolean('external')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('implementation_pages');
    }
};
