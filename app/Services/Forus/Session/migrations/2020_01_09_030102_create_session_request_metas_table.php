<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionRequestMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_request_metas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('session_request_id')->unsigned();
            $table->enum('key', [
                'ip', 'client_type', 'client_version', 'identity_address',
                'identity_proxy_id'
            ]);
            $table->string('value',1024)->nullable();
            $table->timestamps();

            $table->foreign('session_request_id'
            )->references('id')->on('session_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('session_request_metas');
    }
}
