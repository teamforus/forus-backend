<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddBackofficeNotEligibleRedirectUrlToFundConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->enum('backoffice_ineligible_policy', ['redirect', 'fund_request'])
                ->default('fund_request')
                ->after('backoffice_fallback')
                ->nullable();

            $table->string('backoffice_ineligible_redirect_url', 2000)
                ->after('backoffice_ineligible_policy')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('backoffice_ineligible_policy');
            $table->dropColumn('backoffice_ineligible_redirect_url');
        });
    }
}
