<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uid', 255)->nullable();
            $table->string('original_name', 200)->nullable();
            $table->string('ext', 10);

            $table->string('path',1024);
            $table->string('size',100);

            $table->string('identity_address', 200);

            $table->integer('fileable_id')->nullable()->unsigned();
            $table->string('fileable_type', 80)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
