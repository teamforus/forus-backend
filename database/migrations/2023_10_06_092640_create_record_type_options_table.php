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
        Schema::create('record_type_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('record_type_id');
            $table->string('value', 200);
            $table->string('name', 200);
            $table->timestamps();

            $table->foreign('record_type_id')
                ->references('id')->on('record_types')
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
        Schema::dropIfExists('record_type_options');
    }
};
