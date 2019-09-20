<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductRequestIdOnValidatorRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('validator_requests', function (Blueprint $table) {
            $table->unsignedInteger('product_request_id')->nullable()->after('state');

            $table->foreign('product_request_id'
            )->references('id')->on('product_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('validator_requests', function (Blueprint $table) {
            $table->dropForeign('validator_requests_product_request_id_foreign');
            $table->dropColumn('product_request_id');
        });
    }
}
