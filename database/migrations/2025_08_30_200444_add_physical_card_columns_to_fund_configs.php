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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('fund_request_physical_card_enable')
                ->default(false)
                ->after('allow_provider_sign_up');

            $table->unsignedBigInteger('fund_request_physical_card_type_id')
                ->nullable()
                ->default(null)
                ->after('fund_request_physical_card_enable');

            $table->foreign('fund_request_physical_card_type_id')
                ->references('id')
                ->on('physical_card_types')
                ->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn([
                'fund_request_physical_card_enable',
                'fund_request_physical_card_type_id',
            ]);
        });
    }
};
