<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrevalidationRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prevalidation_records', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('record_type_id')->unsigned();
            $table->integer('prevalidation_id')->unsigned();
            $table->string('value')->default('');
            $table->timestamps();

            $table->foreign('record_type_id'
            )->references('id')->on('record_types')->onDelete('cascade');

            $table->foreign('prevalidation_id'
            )->references('id')->on('prevalidations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prevalidation_records');
    }
}
