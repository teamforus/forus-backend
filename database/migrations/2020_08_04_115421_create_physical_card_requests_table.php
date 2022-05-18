<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class CreatePhysicalCardRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('physical_card_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('voucher_id')->unsigned();
            $table->string('address', 200);
            $table->string('house', 20);
            $table->string('house_addition', 20);
            $table->string('postcode', 20);
            $table->string('city', 50);
            $table->timestamps();

            $table->foreign('voucher_id'
            )->references('id')->on('vouchers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_card_requests');
    }
}
