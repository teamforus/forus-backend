<?php

namespace App\Console\Commands\Forus;

use App\Console\Commands\BaseCommand;
use App\Services\Forus\TestData\TestData;
use Exception;
use Throwable;

class TestDataSeedCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-data:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with test data.';

    /**
     * @throws Throwable
     * @return void
     */
    public function handle(): void
    {
        if (config('app.env') == 'production') {
            throw new Exception("Can't be used on production.");
        }

        $startTime = microtime(true);

        $testData = new TestData();
        $testData->disableEmails();

        $testData->info('⇾ Making base identity!');
        $baseIdentity = $testData->makePrimaryIdentity();
        $testData->success('✓ Identity created! Access token: ' . $baseIdentity->proxies[0]->access_token);

        $testData->info('⇾ Making sponsors!');
        $sponsors = $testData->makeSponsors($baseIdentity->address);
        $testData->success('✓ Sponsors created!');
        $testData->separator();

        $testData->info('⇾ Making record types!');
        $testData->makeSponsorRecordTypes();
        $testData->success('✓ Sponsors record types created!');
        $testData->separator();

        $testData->info('⇾ Making funds!');
        $testData->makeSponsorsFunds($sponsors);
        $testData->success('✓ Funds created!');
        $testData->separator();

        $testData->info('⇾ Making providers!');
        $testData->makeProviders($baseIdentity->address);
        $testData->success('✓ Providers created!');
        $testData->separator();

        $testData->info('⇾ Making reservations!');
        $testData->makeReservations($baseIdentity);
        $testData->success('✓ Reservations created!');
        $testData->separator();

        $testData->info('⇾ Making vouchers!');
        $testData->makeVouchers();
        $testData->success('✓ Vouchers created!');
        $testData->separator();

        $testData->info('⇾ Applying providers to funds!');
        $testData->applyFunds($baseIdentity);
        $testData->success('✓ Providers applied to funds!');
        $testData->separator();

        $testData->info('⇾ Making fund requests!');
        $testData->makeFundRequests();
        $testData->success('✓ Fund requests created!');
        $testData->separator();

        $testData->info('⇾ Appending physical cards!');
        $testData->appendPhysicalCards();
        $testData->success('✓ Physical cards attached!');
        $testData->separator();

        $testData->enableEmails();
        $testData->info("\n\TestData - time: " . round(microtime(true) - $startTime, 2) . ' seconds');
    }
}
