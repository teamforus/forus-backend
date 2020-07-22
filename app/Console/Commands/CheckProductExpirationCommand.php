<?php

namespace App\Console\Commands;

use App\Events\Products\ProductExpired;
use App\Events\Products\ProductSoldOut;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CheckProductExpirationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.product.expiration:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle(): void
    {
        $products = Product::where('expire_at', '<', now());
        $products->whereDoesntHave('logs', function(Builder $builder) {
            $builder->where('event', 'expired');
        });

        foreach ($products->get() as $product) {
            ProductExpired::dispatch($product);
        }
    }
}
