<?php

namespace App\Services\BunqService\Commands;

use App\Models\Fund;
use App\Services\BunqService\BunqService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ProcessBunqCheckBunqMeTabsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.bunq:check_bunq_me_tabs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update bunq ideal issuers list.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        try {
            Fund::query()->whereHas('fund_config', function (Builder $builder) {
                $builder->where('is_configured', true);
            })->get()->each(function (Fund $fund) {
                BunqService::processBunqMeTabQueue($fund);
            });
        } catch (\Exception $e) {}
    }
}
