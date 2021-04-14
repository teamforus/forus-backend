<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('fund_id')->unsigned();
            $table->string('identity_address');
            $table->string('identity_bsn');
            $table->string('action');
            $table->string('response_id')->nullable();
            $table->string('state');
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->integer('attempts');
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->foreign('fund_id')->references('id')->on('funds')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_logs');
    }
}
