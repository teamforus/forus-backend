<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('record_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('record_type_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('record_type_id')->unsigned();
            $table->string('locale', 3);
            $table->string('name', 20);

            $table->unique(['record_type_id', 'locale']);
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
        Schema::dropIfExists('record_type_translations');
        Schema::dropIfExists('record_types');
    }
};
