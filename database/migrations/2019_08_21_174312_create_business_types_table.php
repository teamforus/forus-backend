<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBusinessTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key', 120);
            $table->integer('parent_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::create('business_type_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_type_id')->unsigned();
            $table->string('locale', 3);
            $table->string('name', 120);

            $table->unique(['business_type_id', 'locale']);
            $table->foreign('business_type_id'
            )->references('id')->on('business_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_type_translations');
        Schema::dropIfExists('business_types');
    }
}
