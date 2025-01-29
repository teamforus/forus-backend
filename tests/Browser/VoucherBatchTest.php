<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Exception\TimeOutException;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\TestCases\VoucherBatchTestCases;
use Tests\DuskTestCase;
use Tests\Traits\VoucherTestTrait;

class VoucherBatchTest extends DuskTestCase
{
    use WithFaker, HasFrontendActions, VoucherTestTrait;

    /**
     * @var string
     */
    protected string $organizationName = 'Nijmegen';

    /**
     * @var string
     */
    protected string $csvPath = "public/vouchers_batch_test.csv";

    /**
     * @throws \Throwable
     */
    public function testUploadBatchCaseAssignByEmail(): void
    {
        $this->doUploadBatch(VoucherBatchTestCases::$browserTestCaseAssignByEmail);
    }

    /**
     * @throws \Throwable
     */
    public function testUploadBatchCaseAssignByBSN(): void
    {
        $this->doUploadBatch(VoucherBatchTestCases::$browserTestCaseAssignByBSN);
    }

    /**
     * @throws \Throwable
     */
    public function testUploadBatchCaseAssignByClientUID(): void
    {
        $this->doUploadBatch(VoucherBatchTestCases::$browserTestCaseAssignByClientUID);
    }

    /**
     * @throws \Throwable
     */
    public function testUploadBatchCaseAssignByClientUIDSameCode(): void
    {
        $this->doUploadBatch(VoucherBatchTestCases::$browserTestCaseAssignByClientUIDSameCode);
    }

    /**
     * @throws \Throwable
     */
    public function testUploadBatchCaseLowAmounts(): void
    {
        $this->doUploadBatch(VoucherBatchTestCases::$browserTestCaseLowAmounts);
    }

    /**
     * @param $testCase
     * @return void
     * @throws \Throwable
     */
    public function doUploadBatch($testCase): void
    {
        $implementation = Implementation::general();
        $organization = Organization::where('name', $this->organizationName)->first();

        $this->assertNotNull($organization);
        $this->assertNotNull($implementation);

        // configure organization for bsn
        $organization->update(['bsn_enabled' => true]);

        $funds = $organization->funds->take(3);
        $this->assertNotEmpty($funds);

        $this->browse(function (Browser $browser) use ($implementation, $organization, $funds, $testCase) {
            $startDate = now();
            $type = $testCase['assign_by'];
            $count = $testCase['vouchers_count'];
            $sameCode = $testCase['same_code_for_fund'];
            $assertTotalCount = $count * $funds->count();

            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $organization->identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
            $this->selectDashboardOrganization($browser, $organization);
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

            foreach ($vouchers->groupBy('fund_id') as $fundId => $list) {
                $this->switchToFund($browser, $fundId);

                $browser->waitFor('@searchVoucher');
                $browser->waitFor("@vouchersCard$fundId");

                $list->each(fn(Voucher $item) => $this->searchVoucher($browser, $item, $type));
                $list->each(fn(Voucher $item) => $item->delete());
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
        $search = match ($type) {
            'client_uid' => $voucher->client_uid,
            'bsn' => $voucher->identity?->bsn ?? ($voucher->voucher_relation->bsn ?? null),
            'email' => $voucher->identity?->email,
        };

        $browser->waitFor('@searchVoucher');
        $browser->value('@searchVoucher', $search);

        $browser->waitFor("@voucherItem$voucher->id");
        $browser->assertSeeIn("@voucherItem$voucher->id", $search);
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
        array $testCase,
    ): void {
        $browser->waitFor('@uploadVouchersBatchButton');
        $browser->element('@uploadVouchersBatchButton')->click();

        $browser->waitFor('@modalFundSelectSubmit');
        $browser->element('@modalFundSelectSubmit')->click();

        $browser->waitFor('@modalVoucherUpload');

        $browser->waitFor('@selectFileButton');
        $browser->element('@selectFileButton')->click();

        $this->createFile($funds, $testCase);
        $browser->attach('@inputUpload', Storage::path($this->csvPath));

        $browser->waitFor('@uploadFileButton');
        $browser->element('@uploadFileButton')->click();

        if ($testCase['low_amounts']) {
            for ($i = 0; $i < $funds->count(); $i++) {
                $browser->waitFor('@modalDuplicatesPicker');

                $browser->waitFor('@modalDuplicatesPickerConfirm');
                $browser->element('@modalDuplicatesPickerConfirm')->click();
            }

            $browser->waitUntilMissing('@modalDuplicatesPicker');
        }

        $browser->waitFor('@successUploadIcon');

        $browser->element('@closeModalButton')->click();
        $browser->waitUntilMissing('@modalVoucherUpload');

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
        $lowAmounts = $testCase['low_amounts'];

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
            for ($i = 1; $i <= $count; $i++) {
                $amount = $lowAmounts ? rand(1, 5) : rand(6, min($fund->getMaxAmountPerVoucher(), 50));

                fputcsv($handle, array_merge([
                    'fund_id' => $fund->id,
                    'bsn' => $type === 'bsn' ? (string) $this->randomFakeBsn() : null,
                    'email' => $type === 'email' ? $this->makeUniqueEmail() : null,
                    'client_uid' => $type === 'client_uid' ? ($sameCode ? $baseClientUid : Str::random()) : null,
                    'activate' => true,
                    'activation_code' => true,
                    'amount' => $amount,
                    'limit_multiplier' => rand(1, 3),
                    'expire_at' => now()->addDays(30)->format('Y-m-d'),
                    'note' => $this->faker()->sentence(),
                ],
                    $this->directPaymentFields($fund->generatorDirectPaymentsAllowed()),
                    $this->recordsFields(),
                ));
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
        return $allowDirectPayments ? [
            'direct_payment_iban' => $this->faker()->iban('NL'),
            'direct_payment_name' => $this->faker()->firstName . ' ' . $this->faker()->lastName,
        ] : [
            'direct_payment_iban' => '',
            'direct_payment_name' => '',
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
     * @return Collection|Voucher[]
     */
    private function getVouchers(Collection $funds, Carbon $startDate): Collection|array
    {
        return Voucher::query()
            ->whereIn('fund_id', $funds->pluck('id')->all())
            ->whereNull('product_id')
            ->where('created_at', '>=', $startDate)
            ->get();
    }
}
