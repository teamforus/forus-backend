<?php

namespace App\Console\Commands;

use App\Models\FundProviderProduct;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CheckActionExpirationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.action.expiration:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and stop expired actions.';

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
     * @return void
     */
    public function handle()
    {
        /** @var FundProviderProduct[]|Collection $provider_products */
        $provider_products = FundProviderProduct::where(
            'expire_at', '<', now()
        )->get();

        foreach ($provider_products as $provider_product) {
            $provider_product->fund_provider->declineProducts([$provider_product->product_id]);
        }
    }
}
