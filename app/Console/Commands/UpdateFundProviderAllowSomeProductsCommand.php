<?php

namespace App\Console\Commands;

use App\Models\FundProvider;
use Illuminate\Console\Command;

class UpdateFundProviderAllowSomeProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.fund_providers:update-allow-some-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update fund providers \'allow_some_products\' value.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        FundProvider::all()->each(function(FundProvider $fundProvider) {
            $fundProvider->update([
                'allow_some_products' => $fundProvider->fund_provider_products()->count() > 0
            ]);
        });
    }
}
