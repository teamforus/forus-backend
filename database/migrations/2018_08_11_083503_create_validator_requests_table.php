<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateValidatorRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('validator_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('validator_id')->unsigned();
            $table->string('record_validation_uid', 200)->nullable()->default(null);
            $table->string('identity_address', 200);
            $table->integer('record_id')->unsigned();
            $table->string('state');
            $table->timestamp('validated_at');
            $table->timestamps();

            $table->foreign('validator_id')->references('id')
                ->on('validators')->onDelete('cascade');

            $table->foreign('record_validation_uid')->references('uuid')
                ->on('record_validations')->onDelete('cascade');

            $table->foreign('identity_address')->references('address')
                ->on('identities')->onDelete('cascade');

            $table->foreign('record_id')->references('id')
                ->on('records')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('validator_requests');
    }
}
