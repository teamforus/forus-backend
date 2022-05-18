<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class CreateVoucherRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('voucher_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('voucher_id');
            $table->string('bsn', 200)->nullable();
            $table->timestamps();

            $table->foreign('voucher_id')
                ->references('id')->on('vouchers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_relations');
    }
}
