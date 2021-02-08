<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Organization;
use App\Models\Fund;

/**
 * Class AddOrganizationIdFieldToImplementationsTable
 * @noinspection PhpUnused
 */
class AddOrganizationIdFieldToImplementationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->unsignedInteger('organization_id')->nullable()->after('id');
        });

        foreach (Organization::get() as $organization) {
            /** @var Fund $fund */
            $fund = $organization->funds()->whereHas('fund_config.implementation')->first();

            if ($fund) {
                $fund->fund_config->implementation->forceFill([
                    'organization_id' => $organization->id
                ])->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });
    }
}
