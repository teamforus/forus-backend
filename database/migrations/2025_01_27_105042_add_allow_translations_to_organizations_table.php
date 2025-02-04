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
            $table->boolean('allow_translations')
                ->default(0)
                ->after('allow_profiles');

            $table->boolean('translations_enabled')
                ->default(0)
                ->after('reservations_subsidy_enabled');

            $table->unsignedInteger('translations_daily_limit')
                ->default(2_500_000)
                ->after('translations_enabled');

            $table->unsignedInteger('translations_weekly_limit')
                ->default(5_000_000)
                ->after('translations_daily_limit');

            $table->unsignedInteger('translations_monthly_limit')
                ->default(10_000_000)
                ->after('translations_weekly_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('allow_translations');
            $table->dropColumn('translations_enabled');
            $table->dropColumn('translations_daily_limit');
            $table->dropColumn('translations_weekly_limit');
            $table->dropColumn('translations_monthly_limit');
        });
    }
};
