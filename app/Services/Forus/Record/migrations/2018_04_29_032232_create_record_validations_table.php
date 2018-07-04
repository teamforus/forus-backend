<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordValidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('record_validations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid', 200)->unique();
            $table->integer('record_id')->unsigned();
            $table->string('identity_address', 200)->nullable();
            $table->string('state', 20);
            $table->timestamps();

            $table->foreign('record_id'
            )->references('id')->on('records')->onDelete('cascade');

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
        Schema::dropIfExists('record_validations');
    }
}
