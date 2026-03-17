<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Voucher;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VoucherBatchUploadDuplicatesTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestVouchers;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use NavigatesFrontendDashboard;

    /**
     * @var string
     */
    protected string $csvPath = 'public/vouchers_batch_test.csv';

    /**
     * @throws Throwable
     */
    public function testUploadBatchClientUIDDuplicates(): void
    {
        $this->doUploadBatch('client_uid');
    }

    /**
     * @throws Throwable
     */
    public function testUploadBatchBSNDuplicates(): void
    {
        $this->doUploadBatch('bsn');
    }

    /**
     * @throws Throwable
     */
    public function testUploadBatchEmailDuplicates(): void
    {
        $this->doUploadBatch('email');
    }

    /**
     * @throws Throwable
     */
    public function testUploadBatchALlDuplicateTypes(): void
    {
        $this->doUploadBatchWithAllDuplicateTypesAndPartialApprove();
    }

    /**
     * @param string $type
     * @throws Throwable
     * @throws TimeOutException
     * @return void
     */
    protected function doUploadBatch(string $type): void
    {
        $startDate = Carbon::now();
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization);

        // prepare data for each case and create vouchers in db so in data will be duplicate values
        // and count($data) is same as created vouchers
        $data = match ($type) {
            'client_uid' => $this->prepareDataForClientUid($fund, count: 2),
            'bsn' => $this->prepareDataForBsn($fund, count: 2),
            'email' => $this->prepareDataForEmail($fund, count: 2),
        };

        $this->assertVoucherBatchUploadWithDuplicates($implementation, $fund, $startDate, $data);
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param Carbon $startDate
     * @param array $data
     * @throws Throwable
     * @return void
     */
    protected function assertVoucherBatchUploadWithDuplicates(
        Implementation $implementation,
        Fund $fund,
        Carbon $startDate,
        array $data,
    ): void {
        $this->rollbackModels([], function () use ($implementation, $data, $fund, $startDate) {
            $this->browse(function (Browser $browser) use ($implementation, $data, $fund, $startDate) {
                $identity = $fund->organization->identity;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToVouchersPage($browser);

                // assert that count vouchers before upload is same as created when prepare data
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());

                // create file with vouchers and upload it
                $this->createFile($fund, $data);
                $this->uploadVouchersBatch($browser, false);

                // assert that after cancel duplicate approval vouchers count still same
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());

                $this->uploadVouchersBatch($browser, true);

                // assert that after duplicate approval count of vouchers becomes x2
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data) * 2, $vouchers->count());

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
            Storage::delete($this->csvPath);
        });
    }

    /**
     * @throws Throwable
     * @throws TimeOutException
     * @return void
     */
    protected function doUploadBatchWithAllDuplicateTypesAndPartialApprove(): void
    {
        $startDate = Carbon::now();
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization);

        // prepare data for each case and create vouchers in db so in data will be duplicate values
        // and count($data) is same as created vouchers
        $data = $this->prepareDataForAllTypes($fund, count: 2);
        $this->assertVoucherBatchUploadWithPartialApproveDuplicates($implementation, $fund, $startDate, $data);
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param Carbon $startDate
     * @param array $data
     * @throws Throwable
     * @return void
     */
    protected function assertVoucherBatchUploadWithPartialApproveDuplicates(
        Implementation $implementation,
        Fund $fund,
        Carbon $startDate,
        array $data,
    ): void {
        $this->rollbackModels([], function () use ($implementation, $data, $fund, $startDate) {
            $this->browse(function (Browser $browser) use ($implementation, $data, $fund, $startDate) {
                $identity = $fund->organization->identity;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $implementation->organization);

                $this->goToVouchersPage($browser);

                // assert that count vouchers before upload is same as created when prepare data
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());

                // take first item to upload and build values for manual approve only this first row
                $first = $data[0];

                $partialApprove = [
                    'email' => [$first['email']],
                    'bsn' => [$first['bsn']],
                    'client_uid' => [$first['client_uid']],
                ];

                // create file with vouchers and upload it
                $this->createFile($fund, $data);
                // pass $partialApprove for understanding how many duplicate modals we must skip
                $this->uploadVouchersBatchAndApprovePartial($browser, false, $partialApprove);

                // assert that after cancel duplicate approval vouchers count still same
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());

                $this->uploadVouchersBatchAndApprovePartial($browser, true, $partialApprove);

                // assert that after duplicate approval count of vouchers becomes x2
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data) + 1, $vouchers->count());

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
            Storage::delete($this->csvPath);
        });
    }

    /**
     * @return array
     */
    protected function recordsFields(): array
    {
        return [
            'record.given_name' => $this->faker()->firstName(),
            'record.family_name' => $this->faker()->lastName(),
            'record.birth_date' => Carbon::create(2000, 1, 5)->format('Y-m-d'),
            'record.address' => $this->faker()->address(),
        ];
    }

    /**
     * @param Browser $browser
     * @param bool $approveDuplicates
     * @throws TimeOutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    private function uploadVouchersBatch(Browser $browser, bool $approveDuplicates): void
    {
        $this->openUploadModal($browser);
        $this->skipSelectFund($browser);
        $this->attachFile($browser);
        $this->processDuplicates($browser, $approveDuplicates);

        $approveDuplicates && $browser->waitFor('@successUploadIcon');

        $this->closeUploadModal($browser);

        $approveDuplicates && $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @param bool $approveDuplicates
     * @param array $toggleItems
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function uploadVouchersBatchAndApprovePartial(
        Browser $browser,
        bool $approveDuplicates,
        array $toggleItems = []
    ): void {
        $this->openUploadModal($browser);
        $this->skipSelectFund($browser);
        $this->attachFile($browser);
        $this->processPartialDuplicates($browser, $approveDuplicates, $toggleItems);

        $approveDuplicates && $browser->waitFor('@successUploadIcon');

        $this->closeUploadModal($browser);

        $approveDuplicates && $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function openUploadModal(Browser $browser): void
    {
        $browser->waitFor('@uploadVouchersBatchButton');
        $browser->element('@uploadVouchersBatchButton')->click();

        $browser->waitFor('@modalVoucherUpload');
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function skipSelectFund(Browser $browser): void
    {
        $browser->waitFor('@modalFundSelectSubmit');
        $browser->element('@modalFundSelectSubmit')->click();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function attachFile(Browser $browser): void
    {
        $browser->waitFor('@selectFileButton');
        $browser->element('@selectFileButton')->click();

        $browser->attach('@inputUpload', Storage::path($this->csvPath));

        $browser->waitFor('@uploadFileButton');
        $browser->element('@uploadFileButton')->click();
    }

    /**
     * @param Browser $browser
     * @param bool $approveDuplicates
     * @throws TimeoutException
     * @return void
     */
    private function processDuplicates(Browser $browser, bool $approveDuplicates): void
    {
        $browser->waitFor('@modalDuplicatesPicker');

        if ($approveDuplicates) {
            $browser->waitFor('@modalDuplicatesPickerToggleAllOn');
            $browser->element('@modalDuplicatesPickerToggleAllOn')->click();
        }

        $browser->waitFor('@modalDuplicatesPickerConfirm');
        $browser->element('@modalDuplicatesPickerConfirm')->click();

        $browser->waitUntilMissing('@modalDuplicatesPicker');
    }

    /**
     * @param Browser $browser
     * @param bool $approveDuplicates
     * @param array $toggleItems
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function processPartialDuplicates(
        Browser $browser,
        bool $approveDuplicates,
        array $toggleItems = []
    ): void {
        $browser->waitFor('@modalDuplicatesPicker');

        if (count($toggleItems)) {
            foreach ($toggleItems as $items) {
                $browser->waitFor('@duplicateItem');
                $browser->waitForTextIn('@duplicateItem', $items[0]);

                if ($approveDuplicates) {
                    foreach ($items as $item) {
                        foreach ($browser->elements('@duplicateItem') as $index => $element) {
                            if ($element->getText() === (string) $item) {
                                $browser->click("@toggle_duplicate_$index");
                            }
                        }
                    }
                }

                $browser->waitFor('@modalDuplicatesPickerConfirm');
                $browser->element('@modalDuplicatesPickerConfirm')->click();
            }
        }

        $browser->waitUntilMissing('@modalDuplicatesPicker');
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function closeUploadModal(Browser $browser): void
    {
        $browser->element('@closeModalButton')->click();
        $browser->waitUntilMissing('@modalVoucherUpload');
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @return void
     */
    private function createFile(Fund $fund, array $data): void
    {
        $filename = Storage::path($this->csvPath);
        $handle = fopen($filename, 'w');

        fputcsv($handle, [
            'fund_id', 'activate', 'activation_code', 'amount', 'limit_multiplier', 'expire_at', 'note',
            'record.given_name', 'record.family_name', 'record.birth_date', 'record.address',
            ...array_keys(Arr::first($data)),
        ]);

        foreach ($data as $datum) {
            $amount = rand(10, min($fund->getMaxAmountPerVoucher(), 50));

            fputcsv($handle, [
                'fund_id' => $fund->id,
                'activate' => true,
                'activation_code' => true,
                'amount' => $amount,
                'limit_multiplier' => rand(1, 3),
                'expire_at' => now()->addDays(30)->format('Y-m-d'),
                'note' => $this->faker()->sentence(),
                ...$this->recordsFields(),
                ...$datum,
            ]);
        }

        fclose($handle);
    }

    /**
     * @param Fund $fund
     * @param Carbon $startDate
     * @return Collection|Voucher[]
     */
    private function getVouchers(Fund $fund, Carbon $startDate): Collection|array
    {
        return Voucher::query()
            ->where('fund_id', $fund->id)
            ->whereNull('product_id')
            ->where('created_at', '>=', $startDate)
            ->get();
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @return array
     */
    private function prepareDataForClientUid(Fund $fund, int $count): array
    {
        $data = [];
        $employee = $fund->organization->employees->first();

        for ($i = 0; $i < $count; $i++) {
            $uid = Str::random();

            $this->makeTestVoucher($fund, voucherFields: [
                'client_uid' => $uid,
                'employee_id' => $employee->id,
            ]);

            $data[] = ['client_uid' => $uid];
        }

        return $data;
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @throws Throwable
     * @return array
     */
    private function prepareDataForBsn(Fund $fund, int $count): array
    {
        // configure organization for bsn
        $fund->organization->update(['bsn_enabled' => true]);

        $data = [];
        $employee = $fund->organization->employees->first();

        for ($i = 0; $i < $count; $i++) {
            $bsn = $this->randomFakeBsn();
            $identity = $this->makeIdentity(bsn: $bsn);
            $this->makeTestVoucher($fund, $identity, voucherFields: ['employee_id' => $employee->id]);
            $data[] = compact('bsn');
        }

        return $data;
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @return array
     */
    private function prepareDataForEmail(Fund $fund, int $count): array
    {
        $data = [];
        $employee = $fund->organization->employees->first();

        for ($i = 0; $i < $count; $i++) {
            $identity = $this->makeIdentity($email = $this->makeUniqueEmail());
            $this->makeTestVoucher($fund, $identity, voucherFields: ['employee_id' => $employee->id]);
            $data[] = compact('email');
        }

        return $data;
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @throws Throwable
     * @return array
     */
    private function prepareDataForAllTypes(Fund $fund, int $count): array
    {
        $data = [];
        $employee = $fund->organization->employees->first();

        for ($i = 0; $i < $count; $i++) {
            $bsn = $this->randomFakeBsn();
            $email = $this->makeUniqueEmail();
            $identity = $this->makeIdentity(email: $email, bsn: $bsn);
            $uid = Str::random();

            $this->makeTestVoucher($fund, $identity, voucherFields: [
                'client_uid' => $uid,
                'employee_id' => $employee->id,
            ]);

            $data[] = [
                'bsn' => $bsn,
                'email' => $email,
                'client_uid' => $uid,
            ];
        }

        return $data;
    }
}
