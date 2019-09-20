<?php

namespace App\Console\Commands;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Console\Command;

class RegenerateDemoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prepare-demo:barneveld';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare database for Barneveld demo.';

    protected $demoSeeder;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->demoSeeder = new \LoremDbSeederDemo();
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->demoSeeder->info("Cleaning db:");
        $this->demoSeeder->message(str_repeat("-", 80));

        $this->printDeletedCount(
            'Product',
            Product::where('id', '>', 0)->delete()
        );

        $this->printDeletedCount(
            'Fund',
            Fund::where('id', '>', 0)->delete()
        );

        $this->printDeletedCount(
            'Voucher',
            Voucher::where('id', '>', 0)->delete()
        );

        $this->printDeletedCount(
            'Organization',
            Organization::where('id', '>', 0)->delete()
        );

        $this->printDeletedCount(
            'Implementation',
            Implementation::where('id', '>', 0)->delete()
        );


        $this->demoSeeder->info("");
        $this->demoSeeder->info("Running seeder:");
        $this->demoSeeder->message(str_repeat("-", 80));
        $this->demoSeeder->run();

        $this->demoSeeder->message(str_repeat("-", 80));
        $this->demoSeeder->success("Done!");
    }

    public function printDeletedCount($str, $count) {
        $this->demoSeeder->success(
            sprintf("- %s %s deleted", $count, str_plural($str, $count))
        );
    }
}
