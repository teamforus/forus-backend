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
        Schema::create('pre_check_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('record_type_id');
            $table->unsignedInteger('pre_check_id');
            $table->unsignedInteger('order')->nullable();
            $table->string('short_title', 30)->nullable();
            $table->string('title', 50);
            $table->string('description', 1000)->nullable();
            $table->timestamps();

            $table->foreign('pre_check_id')
                ->references('id')
                ->on('pre_checks')
                ->onDelete('cascade');

            $table->foreign('record_type_id')
                ->references('id')
                ->on('record_types')
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
        Schema::dropIfExists('pre_check_records');
    }
};
