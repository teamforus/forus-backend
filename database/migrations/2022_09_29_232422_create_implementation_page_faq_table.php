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
        Schema::create('implementation_page_faq', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('implementation_page_id');
            $table->string('title', 200);
            $table->string('description', 5000);
            $table->timestamps();

            $table->foreign('implementation_page_id')
                ->references('id')->on('implementation_pages')
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
        Schema::dropIfExists('implementation_page_faq');
    }
};