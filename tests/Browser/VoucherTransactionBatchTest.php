<?php

namespace Tests\Browser;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Exception\TimeOutException;
use Tests\DuskTestCase;
use Tests\Traits\MakesVoucherTransaction;

class VoucherTransactionBatchTest extends DuskTestCase
{
    use MakesVoucherTransaction, WithFaker;

    /**
     * @var string
     */
    protected string $implementationName = 'nijmegen';

    /**
     * @var string
     */
    protected string $csvPath = "app/public/transactions_test.csv";

    /**
     * @var int
     */
    protected int $transactionPerVoucher = 10;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUploadBatch(): void
    {
        $implementation = Implementation::byKey($this->implementationName);

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $startDate = now();

        $this->browse(function (Browser $browser) use ($implementation, $startDate) {
            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $proxy = $this->makeIdentityProxy($implementation->organization->identity);
            $browser->script("localStorage.setItem('active_account', '$proxy->access_token')");
            $browser->refresh();

            $browser->waitFor('@fundsTitle', 10);

            $browser->waitFor('@identityEmail');
            $browser->assertSeeIn('@identityEmail', $implementation->organization->identity->email);
            $browser->waitFor('@headerOrganizationSwitcher');
            $browser->press('@headerOrganizationSwitcher');
            $browser->waitFor("@headerOrganizationItem$implementation->organization_id");
            $browser->press("@headerOrganizationItem$implementation->organization_id");
            $browser->pause(5000);

            $this->goToTransactionsPage($browser);

            $voucher = $this->getVouchersForBatchTransactionsQuery($implementation->organization)->first();
            $this->assertNotNull($voucher);

            // create file with transactions for voucher and upload it
            $this->uploadTransactionsPage($browser, $voucher);

            // check transaction exists
            $transactions = $this->getTransactions($implementation->organization, $voucher, $startDate);
            $this->assertEquals($transactions->count(), $this->transactionPerVoucher);

            foreach ($transactions as $transaction) {
                $this->searchTransaction($browser, $transaction);
            }

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @param VoucherTransaction $transaction
     * @return void
     * @throws TimeOutException
     */
    private function searchTransaction(Browser $browser, VoucherTransaction $transaction): void
    {
        $browser->waitFor('@searchTransaction');
        $browser->type('@searchTransaction', $transaction->uid);

        $browser->pause(2000);
        $browser->waitFor('@transactionItem');
        $browser->assertSeeIn('@transactionItem', $transaction->uid);

        $browser->within('@transactionItem', function(Browser $browser) use ($transaction) {
            $browser->assertSeeIn('@transactionState', $transaction->state_locale);
        });
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function goToTransactionsPage(Browser $browser): void
    {
        $browser->waitFor('@transactionsPage');
        $browser->element('@transactionsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @return void
     * @throws TimeOutException
     */
    private function uploadTransactionsPage(Browser $browser, Voucher $voucher): void
    {
        $browser->waitFor('@uploadTransactionsBatchButton');
        $browser->element('@uploadTransactionsBatchButton')->click();

        $browser->waitFor('@modalTransactionUpload');

        $browser->waitFor('@selectFileButton');
        $browser->element('@selectFileButton')->click();

        $this->createFile($voucher);
        $browser->attach('@inputUpload', storage_path($this->csvPath));

        $browser->waitFor('@uploadFileButton');
        $browser->element('@uploadFileButton')->click();

        $browser->waitFor('@successUploadIcon');

        $browser->element('@closeModalButton')->click();

        File::delete(storage_path($this->csvPath));
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    private function createFile(Voucher $voucher): void
    {
        $filename = storage_path($this->csvPath);
        $handle = fopen($filename, 'w');

        fputcsv($handle, [
            'voucher_id', 'amount', 'direct_payment_iban', 'direct_payment_name', 'uid', 'note',
        ]);

        $amount = $voucher->amount_available / $this->transactionPerVoucher;

        for ($i = 1; $i <= $this->transactionPerVoucher; $i++) {
            $transaction = [
                'voucher_id' => $voucher->id,
                'amount' => $amount,
                'direct_payment_iban' => $this->faker()->iban('NL'),
                'direct_payment_name' => $this->faker()->firstName . ' ' . $this->faker()->lastName,
                'uid' => Str::random(15),
                'note' => $this->faker()->sentence(),
            ];

            fputcsv($handle, $transaction);
        }

        fclose($handle);
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function logout(Browser $browser): void
    {
        $browser->refresh();

        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();

        $browser->waitFor('@btnUserLogout');
        $browser->element('@btnUserLogout')->click();
    }

    /**
     * @param Organization $organization
     * @param Voucher $voucher
     * @param Carbon $startDate
     * @return Collection
     */
    private function getTransactions(
        Organization $organization,
        Voucher $voucher,
        Carbon $startDate
    ): Collection {
        $query = VoucherTransaction::query()
            ->where('voucher_id', $voucher->id)
            ->where('created_at', '>=', $startDate);

        $builder = new VoucherTransactionsSearch([], $query);

        return $builder->searchSponsor($organization)->get();
    }
}
