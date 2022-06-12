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
    public function up()
    {
        Schema::create('implementation_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('implementation_page_id');
            $table->enum('type', ['detailed', 'text'])->default('text');
            $table->string('key', 50);
            $table->string('label', 200)->nullable();
            $table->string('title', 200)->nullable();
            $table->string('description', 5000)->nullable();
            $table->boolean('button_enabled')->default(false);
            $table->string('button_text', 200)->nullable();
            $table->string('button_link', 200)->nullable();
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
    public function down()
    {
        Schema::dropIfExists('implementation_blocks');
    }
};
