<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up(): void
    {
        Schema::create('implementation_wallet_flows', function (Blueprint $table) {
            $table->unsignedInteger('implementation_id');
            $table->unsignedBigInteger('wallet_flow_id');
            $table->timestamps();

            $table->unique(['implementation_id', 'wallet_flow_id'], 'implementation_wallet_flows_unique');

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('restrict');

            $table->foreign('wallet_flow_id')
                ->references('id')
                ->on('wallet_flows')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down(): void
    {
        Schema::drop('implementation_wallet_flows');
    }
};
