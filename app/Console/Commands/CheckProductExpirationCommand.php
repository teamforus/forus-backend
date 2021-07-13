<?php

namespace App\Console\Commands;

use App\Events\Products\ProductExpired;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class CheckProductExpirationCommand
 * @package App\Console\Commands
 */
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
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $products = Product::whereDate('expire_at', '<', now());
        $products->whereDoesntHave('logs', function(Builder $builder) {
            $builder->where('event', 'expired');
        });

        foreach ($products->get() as $product) {
            ProductExpired::dispatch($product);
        }
    }
}
