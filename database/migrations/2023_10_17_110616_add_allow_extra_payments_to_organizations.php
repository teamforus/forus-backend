<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->boolean('allow_extra_payments')
                ->default(false)
                ->after('allow_some_products');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_provider_extra_payments')
                ->default(false)
                ->after('allow_bi_connection');

            $table->boolean('allow_reservation_extra_payments')
                ->default(false)
                ->after('allow_provider_extra_payments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->dropColumn('allow_extra_payments');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['allow_reservation_extra_payments', 'allow_provider_extra_payments']);
        });
    }
};
