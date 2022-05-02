<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

/**
 * @noinspection PhpUnused
 */
class AddStateColumnToFundProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->enum('state', ['pending', 'accepted', 'rejected'])
                ->after('allow_some_products')
                ->default('pending');
        });

        $this->migrateFundProviders();
    }

    /**
     * @return void
     */
    protected function migrateFundProviders()
    {
        $sponsors = Organization::whereHas('funds.providers')->get();

        foreach ($sponsors as $sponsor) {
            $this->migrateSponsorProvider($sponsor);
        }
    }

    /**
     * @param Organization $sponsor
     * @return void
     */
    protected function migrateSponsorProvider(Organization $sponsor)
    {
        $fundsQuery = $sponsor->funds()->select('funds.id');
        $providersQuery = FundProvider::whereIn('fund_id', $fundsQuery->getQuery());

        $acceptedQuery = (clone $providersQuery)->where(function(Builder $builder) {
            $builder->where('allow_budget', true);
            $builder->orWhere('allow_products', true);
            $builder->orWhereHas('fund_provider_products');
        });

        $rejectedQuery = (clone $providersQuery)->where(function(Builder $builder) use ($acceptedQuery, $fundsQuery) {
            $builder->whereNotIn('fund_providers.id', (clone $acceptedQuery)->select('fund_providers.id'));
            $builder->whereHas('organization', function(Builder $builder) use ($fundsQuery) {
                $builder->whereHas('voucher_transactions.voucher', function(Builder $builder) use ($fundsQuery) {
                    $builder->whereIn('fund_id', (clone $fundsQuery)->select('funds.id')->getQuery());
                });
            });
        });

        $acceptedQuery = FundProvider::whereIn('id', $acceptedQuery->pluck('id'));
        $rejectedQuery = FundProvider::whereIn('id', $rejectedQuery->pluck('id'));

        $acceptedQuery->update([
            'fund_providers.state' => 'accepted',
        ]);

        $rejectedQuery->update([
            'fund_providers.state' => 'rejected',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
}
