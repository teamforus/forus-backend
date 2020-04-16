<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\FundProvider;

class RemoveDuplicatedFundProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Find 'initial' fund provider records among duplicated ones
        $initialRecords = FundProvider::query()->select(
            'id', 'organization_id', 'fund_id', DB::raw('count(*) as total')
        )->groupBy(['organization_id', 'fund_id'])->having(
            'total', '>', 1
        )->get();

        $initialRecords->each(function (FundProvider $fundProvider) {
            FundProvider::query()->where([
                'organization_id' => $fundProvider->organization_id,
                'fund_id'         => $fundProvider->fund_id,
            ])->where(
                'id', '!=', $fundProvider->id
            )->delete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
