<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrevalidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prevalidations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uid')->nullable()->default(null);
            $table->string('identity_address');
            $table->string('state')->default("pending");
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
        Schema::dropIfExists('prevalidations');
    }
}
