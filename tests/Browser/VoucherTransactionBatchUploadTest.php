<?php

namespace Tests\Browser;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesVoucherTransaction;
use Throwable;

class VoucherTransactionBatchUploadTest extends DuskTestCase
{
    use MakesVoucherTransaction;
    use WithFaker;
    use HasFrontendActions;

    /**
     * @var string
     */
    protected string $implementationName = 'nijmegen';

    /**
     * @var string
     */
    protected string $csvPath = 'public/transactions_batch_test.csv';

    /**
     * @var int
     */
    protected int $transactionPerVoucher = 10;

    /**
     * @throws Throwable
     * @return void
     */
    public function testUploadBatch(): void
    {
        $implementation = Implementation::byKey($this->implementationName);

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $this->browse(function (Browser $browser) use ($implementation) {
            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $implementation->organization->identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
            $this->selectDashboardOrganization($browser, $implementation->organization);

            $this->goToTransactionsPage($browser);

            $startDate = now();
            $voucher = $this->getVouchersForBatchTransactionsQuery($implementation->organization)->first();
            $this->assertNotNull($voucher);

            // create file with transactions for voucher and upload it
            $this->uploadTransactionsBatch($browser, $voucher);

            // check transaction exists
            $transactions = $this->getTransactions($implementation->organization, $voucher, $startDate);
            $this->assertEquals($this->transactionPerVoucher, $transactions->count());

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
     * @throws TimeOutException
     * @return void
     */
    private function searchTransaction(Browser $browser, VoucherTransaction $transaction): void
    {
        $browser->waitFor('@searchTransaction');
        $browser->value('@searchTransaction', $transaction->uid);

        $browser->waitFor("@transactionItem$transaction->id");
        $browser->within("@transactionItem$transaction->id", function (Browser $browser) use ($transaction) {
            $browser->assertSee($transaction->uid);
            $browser->assertSeeIn('@transactionState', $transaction->state_locale);
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeOutException
     * @return void
     */
    private function goToTransactionsPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupFinancial');
        $browser->element('@asideMenuGroupFinancial')->click();
        $browser->waitFor('@transactionsPage');
        $browser->element('@transactionsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @throws TimeOutException
     * @return void
     */
    private function uploadTransactionsBatch(Browser $browser, Voucher $voucher): void
    {
        $browser->waitFor('@uploadTransactionsBatchButton');
        $browser->element('@uploadTransactionsBatchButton')->click();

        $browser->waitFor('@modalTransactionUpload');

        $browser->waitFor('@selectFileButton');
        $browser->element('@selectFileButton')->click();

        $this->createFile($voucher);
        $browser->attach('@inputUpload', Storage::path($this->csvPath));

        $browser->waitFor('@uploadFileButton');
        $browser->element('@uploadFileButton')->click();

        $browser->waitFor('@successUploadIcon');

        $browser->element('@closeModalButton')->click();

        Storage::delete($this->csvPath);
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    private function createFile(Voucher $voucher): void
    {
        $filename = Storage::path($this->csvPath);
        $handle = fopen($filename, 'w');

        fputcsv($handle, [
            'voucher_number', 'amount', 'direct_payment_iban', 'direct_payment_name', 'uid', 'note',
        ]);

        for ($i = 1; $i <= $this->transactionPerVoucher; $i++) {
            fputcsv($handle, [
                'voucher_number' => $voucher->number,
                'amount' => $voucher->amount_available / $this->transactionPerVoucher,
                'direct_payment_iban' => $this->faker()->iban('NL'),
                'direct_payment_name' => $this->faker()->firstName . ' ' . $this->faker()->lastName,
                'uid' => Str::random(15),
                'note' => $this->faker()->sentence(),
            ]);
        }

        fclose($handle);
    }

    /**
     * @param Browser $browser
     * @throws TimeOutException
     * @return void
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
