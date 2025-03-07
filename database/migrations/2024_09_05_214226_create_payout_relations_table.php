<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payout_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('voucher_transaction_id')->nullable();
            $table->enum('type', ['bsn', 'email'])->nullable();
            $table->string('value', 200)->nullable();
            $table->timestamps();

            $table->foreign('voucher_transaction_id')
                ->references('id')->on('voucher_transactions')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_relations');
    }
};
