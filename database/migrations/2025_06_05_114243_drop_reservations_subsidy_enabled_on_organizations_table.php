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
        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('reservations_budget_enabled', 'reservations_enabled');
            $table->dropColumn('reservations_subsidy_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('reservations_enabled', 'reservations_budget_enabled');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('reservations_subsidy_enabled')->after('reservations_budget_enabled')->default(0);
        });
    }
};
