<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Exception\TimeOutException;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Configs\VoucherBatchTestConfig;
use Tests\DuskTestCase;
use Tests\Traits\VoucherTestTrait;

class VoucherBatchTest extends DuskTestCase
{
    use WithFaker, HasFrontendActions, VoucherTestTrait;

    /**
     * @var string
     */
    protected string $implementationName = 'nijmegen';

    /**
     * @var string
     */
    protected string $csvPath = "public/vouchers_batch_test.csv";

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUploadBatch(): void
    {
        $implementation = Implementation::byKey($this->implementationName);

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        // configure organization for bsn
        $implementation->organization->update(['bsn_enabled' => true]);

        $funds = $implementation->organization->funds;
        $this->assertNotEmpty($funds);

        $this->browse(function (Browser $browser) use ($implementation, $funds) {
            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $implementation->organization->identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
            $this->selectDashboardOrganization($browser, $implementation->organization);

            foreach (VoucherBatchTestConfig::$browserTestCases as $testCase) {
                $startDate = now();
                $type = $testCase['assign_by'];
                $count = $testCase['vouchers_count'];
                $sameCode = $testCase['same_code_for_fund'];
                $assertTotalCount = $count * $funds->count();

                $this->goToVouchersPage($browser);

                // create file with vouchers and upload it
                $this->uploadVouchersBatch($browser, $funds, $testCase);

                // check vouchers exists
                $vouchers = $this->getVouchers($funds, $startDate);
                $this->assertEquals($assertTotalCount, $vouchers->count());

                if ($type === 'client_uid' && $sameCode) {
                    $this->assertEquals(
                        $assertTotalCount,
                        $vouchers->groupBy('activation_code')->first()->count()
                    );
                }

                $selectedFund = null;
                /** @var Voucher $voucher */
                foreach ($vouchers as $voucher) {
                    if ($selectedFund !== $voucher->fund->id) {
                        $this->switchToFund($browser, $voucher->fund);
                        $selectedFund = $voucher->fund->id;
                    }

                    $this->searchVoucher($browser, $voucher, $type);
                }

                $vouchers->each->delete();
            }

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @param string $type
     * @return void
     * @throws TimeOutException
     */
    private function searchVoucher(Browser $browser, Voucher $voucher, string $type = 'client_uid'): void
    {
        $search = match($type) {
            'client_uid' => $voucher->client_uid,
            'bsn' => $voucher->identity?->bsn ?? ($voucher->voucher_relation->bsn ?? null),
            'email' => $voucher->identity?->email,
        };

        $browser->waitFor('@searchVoucher');
        $browser->type('@searchVoucher', $search);

        $browser->pause(2000);
        $browser->waitFor('@voucherItem');
        $browser->assertSeeIn('@voucherItem', $search);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @return void
     * @throws TimeOutException
     */
    private function switchToFund(Browser $browser, Fund $fund): void
    {
        $browser->waitFor("@fundSelectorOption$fund->id");
        $browser->element("@fundSelectorOption$fund->id")->click();
        $browser->pause(2000);
        $browser->waitFor('@vouchersTitle');
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function goToVouchersPage(Browser $browser): void
    {
        $browser->waitFor('@vouchersPage');
        $browser->element('@vouchersPage')->click();
        $browser->waitFor('@vouchersTitle');
    }

    /**
     * @param Browser $browser
     * @param Collection $funds
     * @param array $testCase
     * @return void
     * @throws TimeOutException
     * @throws \Throwable
     */
    private function uploadVouchersBatch(
        Browser $browser,
        Collection $funds,
        array $testCase
    ): void {
        $browser->waitFor('@uploadVouchersBatchButton');
        $browser->element('@uploadVouchersBatchButton')->click();

        $browser->waitFor('@modalVoucherUpload');

        $browser->waitFor('@selectFileButton');
        $browser->element('@selectFileButton')->click();

        $this->createFile($funds, $testCase);
        $browser->attach('@inputUpload', Storage::path($this->csvPath));

        $browser->waitFor('@uploadFileButton');
        $browser->element('@uploadFileButton')->click();

        $browser->waitFor('@successUploadIcon', 60);

        $browser->element('@closeModalButton')->click();

        Storage::delete($this->csvPath);
    }

    /**
     * @param Collection $funds
     * @param array $testCase
     * @return void
     * @throws \Throwable
     */
    private function createFile(Collection $funds, array $testCase): void
    {
        $type = $testCase['assign_by'];
        $count = $testCase['vouchers_count'];
        $sameCode = $testCase['same_code_for_fund'];

        $filename = Storage::path($this->csvPath);
        $handle = fopen($filename, 'w');

        fputcsv($handle, [
            'fund_id', 'bsn', 'email', 'client_uid', 'activate', 'activation_code', 'amount',
            'limit_multiplier', 'expire_at', 'note', 'direct_payment_iban', 'direct_payment_name',
            'record.given_name', 'record.family_name', 'record.birth_date', 'record.address',
        ]);

        $baseClientUid = Str::random();

        /** @var Fund $fund */
        foreach ($funds as $fund) {
            $allowDirectPayments = $fund->generatorDirectPaymentsAllowed();

            for ($i = 1; $i <= $count; $i++) {
                $data = array_merge([
                    'fund_id' => $fund->id,
                    'bsn' => $type === 'bsn' ? (string) $this->randomFakeBsn() : null,
                    'email' => $type === 'email' ? $this->makeUniqueEmail() : null,
                    'client_uid' => $type === 'client_uid' ? ($sameCode ? $baseClientUid : Str::random()) : null,
                    'activate' => true,
                    'activation_code' => true,
                    'amount' => rand(1, $fund->getMaxAmountPerVoucher()),
                    'limit_multiplier' => rand(1, 3),
                    'expire_at' => now()->addDays(30)->format('Y-m-d'),
                    'note' => $this->faker()->sentence(),
                ],
                    $this->directPaymentFields($allowDirectPayments),
                    $this->recordsFields(),
                );

                fputcsv($handle, $data);
            }
        }

        fclose($handle);
    }

    /**
     * @param bool $allowDirectPayments
     * @return array
     */
    protected function directPaymentFields(bool $allowDirectPayments): array
    {
        return [
            'direct_payment_iban' => $allowDirectPayments ? $this->faker()->iban('NL') : '',
            'direct_payment_name' => $allowDirectPayments
                ? $this->faker()->firstName . ' ' . $this->faker()->lastName
                : '',
        ];
    }

    /**
     * @return array
     */
    protected function recordsFields(): array
    {
        return [
            'record.given_name' => $this->faker()->firstName,
            'record.family_name' => $this->faker()->lastName,
            'record.birth_date' => Carbon::create(2000, 1, 5)->format('Y-m-d'),
            'record.address' => $this->faker()->address,
        ];
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
     * @param Collection $funds
     * @param Carbon $startDate
     * @return Collection
     */
    private function getVouchers(
        Collection $funds,
        Carbon $startDate
    ): Collection {
        return Voucher::query()
            ->whereIn('fund_id', $funds->pluck('id')->all())
            ->whereNull('product_id')
            ->where('created_at', '>=', $startDate)
            ->get();
    }
}
