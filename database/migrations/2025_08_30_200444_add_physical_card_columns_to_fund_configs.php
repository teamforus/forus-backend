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
            $table->boolean('allow_physical_card_requests')
                ->default(false)
                ->after('allow_physical_cards');

            $table->boolean('allow_physical_card_linking')
                ->default(false)
                ->after('allow_physical_card_requests');

            $table->boolean('allow_physical_card_deactivation')
                ->default(false)
                ->after('allow_physical_card_linking');

            $table->boolean('allow_physical_cards_on_application')
                ->default(false)
                ->after('allow_physical_card_deactivation');

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
                'allow_physical_card_requests',
                'allow_physical_card_linking',
                'allow_physical_card_deactivation',
                'allow_physical_cards_on_application',
                'fund_request_physical_card_enable',
                'fund_request_physical_card_type_id',
            ]);
        });
    }
};
