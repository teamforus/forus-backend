<?php

namespace App\Console\Commands;

use Database\Seeders\ProductCategoriesTableSeeder;
use Illuminate\Console\Command;

class UpdateProductCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeders:update-product-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update product categories.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ProductCategoriesTableSeeder::seedProducts(true);

        $this->info('Product categories updated!');
    }
}
