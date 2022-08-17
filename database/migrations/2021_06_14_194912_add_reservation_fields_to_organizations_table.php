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
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('reservations_budget_enabled')
                ->after('validator_auto_accept_funds')->default(1);

            $table->boolean('reservations_subsidy_enabled')
                ->after('reservations_budget_enabled')->default(0);

            $table->boolean('reservations_auto_accept')->default(0)
                ->after('reservations_subsidy_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'reservations_budget_enabled',
                'reservations_subsidy_enabled',
                'reservations_auto_accept'
            ]);
        });
    }
};
