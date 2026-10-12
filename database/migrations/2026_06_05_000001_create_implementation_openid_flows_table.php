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
        Schema::create('implementation_openid_flows', function (Blueprint $table) {
            $table->unsignedInteger('implementation_id');
            $table->unsignedBigInteger('openid_flow_id');
            $table->timestamps();

            $table->unique(['implementation_id', 'openid_flow_id'], 'implementation_openid_flows_unique');

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('restrict');

            $table->foreign('openid_flow_id')
                ->references('id')
                ->on('openid_flows')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('implementation_openid_flows');
    }
};
