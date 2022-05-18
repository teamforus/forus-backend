<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

/**
 * Class CheckOrganizationOfficePostcodesCommand
 * @package App\Console\Commands
 * @noinspection PhpUnused
 */
class CheckOrganizationOfficePostcodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.providers.postcodes:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks and updates office postcodes if needed.
                            {--organization_id= : organization id to process.}';

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
        $organization_id = $this->getOption('organization_id');

        try {
            $organizations = $organization_id ? [Organization::find($organization_id)] : Organization::all();

            foreach ($organizations as $organization) {
                foreach ($organization->offices as $office) {
                    $office->updateGeoData();
                }
            }
        } catch (\Throwable $e) {}
    }

    /**
     * @param string $argument
     * @param null $default
     * @return array|string|null
     */
    protected function getOption(string $argument, $default = null)
    {
        return $this->hasOption($argument) ? $this->option($argument) : $default;
    }
}
