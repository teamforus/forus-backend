<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFundRequestRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_request_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fund_request_id');
            $table->string('record_type_key');
            $table->string('value', 200)->default('');
            $table->string('note', 2000)->default('');
            $table->enum('state', [
                'pending', 'approved', 'declined',
            ])->default('pending');
            $table->timestamps();

            $table->foreign('fund_request_id'
            )->references('id')->on('fund_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_request_records');
    }
}
