<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('record_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('identity_address', 200);
            $table->string('name', 20);
            $table->integer('order')->unsigned()->default(0);
            $table->timestamps();

            $table->foreign('identity_address'
            )->references('address')->on('identities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('record_categories');
    }
}
