<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddBackofficeNotEligibleRedirectUrlToFundConfigs
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
            $table->string('backoffice_not_eligible_redirect_url', 200)
                ->nullable()
                ->after('backoffice_fallback');
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
            $table->dropColumn('backoffice_not_eligible_redirect_url');
        });
    }
}
