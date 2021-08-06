<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundBackofficeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_backoffice_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('fund_id')->nullable();
            $table->string('identity_address', 200)->nullable();
            $table->string('bsn')->nullable();
            $table->string('action', 100);
            $table->enum('state', ['pending', 'success', 'error'])->default('pending');
            $table->string('response_id', 200)->nullable();
            $table->string('response_code', 10)->nullable();
            $table->json('response_body')->nullable();
            $table->string('response_error', 500)->nullable();
            $table->integer('attempts');
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->foreign('fund_id')->references('id')->on('funds')
                ->onDelete('set null');

            $table->foreign('identity_address')->references('address')->on('identities')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_backoffice_logs');
    }
}
