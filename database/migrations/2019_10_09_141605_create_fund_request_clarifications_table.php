<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFundRequestClarificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_request_clarifications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fund_request_record_id');
            $table->string('question', 2000);
            $table->string('answer', 2000);
            $table->enum('state', [
                'pending', 'answered',
            ])->default('pending');
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->foreign('fund_request_record_id'
            )->references('id')->on('fund_request_records')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_request_clarifications');
    }
}
