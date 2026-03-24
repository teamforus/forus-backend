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
        $this->runSingleDuplicateTypeCancelThenApproveFlow('client_uid');
    }

    /**
     * @throws Throwable
     */
    public function testUploadBatchBSNDuplicates(): void
    {
        $this->runSingleDuplicateTypeCancelThenApproveFlow('bsn');
    }

    /**
     * @throws Throwable
     */
    public function testUploadBatchEmailDuplicates(): void
    {
        $this->runSingleDuplicateTypeCancelThenApproveFlow('email');
    }

    /**
     * @throws Throwable
     */
    public function testUploadBatchAllDuplicateTypes(): void
    {
        $this->runAllDuplicateTypesPartialApprovalUploadFlow();
    }

    /**
     * @throws Throwable
     */
    public function testUploadBatchAllDuplicateTypesFromSameVoucher(): void
    {
        $startDate = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);
        $fund = $this->makeTestFund($organization);
        $data = $this->seedExistingVouchersAndBuildCsvRowsForAllDuplicateTypes($fund, count: 1);

        $this->assertDuplicateUploadApproveOnlyFlow($fund, $startDate, $data);
    }

    /**
     * @throws Throwable
     */
    public function testUploadBatchClientUIDDuplicatesCaseInsensitive(): void
    {
        $startDate = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);
        $fund = $this->makeTestFund($organization);
        $data = $this->seedExistingVouchersAndBuildCsvRowsForClientUid($fund, count: 2, lowercaseCsvValue: true);

        $this->assertDuplicateUploadCancelThenApproveFlow($fund, $startDate, $data);
    }

    /**
     * @param string $type
     * @throws Throwable
     * @throws TimeOutException
     * @return void
     */
    protected function runSingleDuplicateTypeCancelThenApproveFlow(string $type): void
    {
        $startDate = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);

        $fund = $this->makeTestFund($organization);

        // Seed existing vouchers so each uploaded row matches an existing duplicate value.
        // The number of CSV rows matches the number of seeded vouchers.
        $data = match ($type) {
            'client_uid' => $this->seedExistingVouchersAndBuildCsvRowsForClientUid($fund, count: 2),
            'bsn' => $this->seedExistingVouchersAndBuildCsvRowsForBsn($fund, count: 2),
            'email' => $this->seedExistingVouchersAndBuildCsvRowsForEmail($fund, count: 2),
        };

        $this->assertDuplicateUploadCancelThenApproveFlow($fund, $startDate, $data);
    }

    /**
     * @param Fund $fund
     * @param Carbon $startDate
     * @param array $data
     * @throws Throwable
     * @return void
     */
    protected function assertDuplicateUploadCancelThenApproveFlow(Fund $fund, Carbon $startDate, array $data): void
    {
        $this->rollbackModels([], function () use ($data, $fund, $startDate) {
            $this->browse(function (Browser $browser) use ($data, $fund, $startDate) {
                $implementation = Implementation::general();
                $identity = $fund->organization->identity;
                $browser->visit($implementation->urlSponsorDashboard());

                // Log in as the sponsor identity.
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $fund->organization);
                $this->goToVouchersPage($browser);

                // Confirm the seeded voucher count matches the CSV rows we will upload.
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());

                // Upload once and cancel on the duplicate picker.
                $this->createFile($fund, $data);
                $this->uploadBatchAndHandleDuplicatePickers($browser, false);

                // Canceling the duplicate picker must not create any extra vouchers.
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());
                $this->uploadBatchAndHandleDuplicatePickers($browser, true);

                // Approving the duplicates should create the same number of new vouchers.
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
    protected function runAllDuplicateTypesPartialApprovalUploadFlow(): void
    {
        $startDate = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);
        $fund = $this->makeTestFund($organization);

        // Seed existing vouchers so each uploaded row matches an existing duplicate value.
        // The number of CSV rows matches the number of seeded vouchers.
        $data = $this->seedExistingVouchersAndBuildCsvRowsForAllDuplicateTypes($fund, count: 2);
        $this->assertDuplicateUploadPartialApprovalFlow($fund, $startDate, $data);
    }

    /**
     * @param Fund $fund
     * @param Carbon $startDate
     * @param array $data
     * @throws Throwable
     * @return void
     */
    protected function assertDuplicateUploadPartialApprovalFlow(Fund $fund, Carbon $startDate, array $data): void
    {
        $this->rollbackModels([], function () use ($data, $fund, $startDate) {
            $this->browse(function (Browser $browser) use ($data, $fund, $startDate) {
                $implementation = Implementation::general();
                $identity = $fund->organization->identity;
                $browser->visit($implementation->urlSponsorDashboard());

                // Log in as the sponsor identity.
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $fund->organization);

                $this->goToVouchersPage($browser);

                // Confirm the seeded voucher count matches the CSV rows we will upload.
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());

                // Approve only the first duplicate item in each picker.
                $first = $data[0];

                $partialApprove = [
                    'email' => [$first['email']],
                    'bsn' => [$first['bsn']],
                    'client_uid' => [$first['client_uid']],
                ];

                // Upload once and cancel after opening the duplicate pickers.
                $this->createFile($fund, $data);
                // Pass the values we want to approve so the helper can select them in each picker.
                $this->uploadBatchAndPartiallyApproveDuplicates($browser, false, $partialApprove);

                // Canceling the duplicate picker must not create any extra vouchers.
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());

                $this->uploadBatchAndPartiallyApproveDuplicates($browser, true, $partialApprove);

                // Only the approved duplicate rows should be created.
                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data) + 1, $vouchers->count());

                // Log out to keep the browser state clean for the next run.
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
            Storage::delete($this->csvPath);
        });
    }

    /**
     * @param Fund $fund
     * @param Carbon $startDate
     * @param array $data
     * @throws Throwable
     * @return void
     */
    protected function assertDuplicateUploadApproveOnlyFlow(Fund $fund, Carbon $startDate, array $data): void
    {
        $this->rollbackModels([], function () use ($data, $fund, $startDate) {
            $this->browse(function (Browser $browser) use ($data, $fund, $startDate) {
                $implementation = Implementation::general();
                $identity = $fund->organization->identity;
                $browser->visit($implementation->urlSponsorDashboard());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $fund->organization);
                $this->goToVouchersPage($browser);

                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data), $vouchers->count());

                $this->createFile($fund, $data);
                $this->uploadBatchAndHandleDuplicatePickers($browser, true);

                $vouchers = $this->getVouchers($fund, $startDate);
                $this->assertEquals(count($data) * 2, $vouchers->count());

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
    private function uploadBatchAndHandleDuplicatePickers(Browser $browser, bool $approveDuplicates): void
    {
        $this->openUploadModal($browser);
        $this->skipSelectFund($browser);
        $this->attachFile($browser);
        $this->handleDuplicatePickers($browser, $approveDuplicates);

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
    private function uploadBatchAndPartiallyApproveDuplicates(
        Browser $browser,
        bool $approveDuplicates,
        array $toggleItems = []
    ): void {
        $this->openUploadModal($browser);
        $this->skipSelectFund($browser);
        $this->attachFile($browser);
        $this->handleDuplicatePickersWithManualSelection($browser, $approveDuplicates, $toggleItems);

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
    private function handleDuplicatePickers(Browser $browser, bool $approveDuplicates): void
    {
        $browser->waitFor('@modalDuplicatesPicker');

        // The upload flow can show multiple duplicate pickers in sequence for email, BSN, and client UID.
        // Keep confirming until none remain.
        for ($i = 0; $i < 5 && count($browser->elements('@modalDuplicatesPicker')) > 0; $i++) {
            if ($approveDuplicates && count($browser->elements('@modalDuplicatesPickerToggleAllOn')) > 0) {
                // Some pickers require explicitly selecting all rows before confirming.
                $browser->element('@modalDuplicatesPickerToggleAllOn')->click();
            }

            $browser->waitFor('@modalDuplicatesPickerConfirm');
            $browser->element('@modalDuplicatesPickerConfirm')->click();

            // Give the next picker a moment to replace the current one.
            $browser->pause(300);
        }

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
    private function handleDuplicatePickersWithManualSelection(
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
     * @param bool $lowercaseCsvValue
     * @return array
     */
    private function seedExistingVouchersAndBuildCsvRowsForClientUid(
        Fund $fund,
        int $count,
        bool $lowercaseCsvValue = false
    ): array {
        $data = [];
        $employee = $fund->organization->employees->first();

        for ($i = 0; $i < $count; $i++) {
            $uid = strtoupper(Str::random());

            $this->makeTestVoucher($fund, voucherFields: [
                'client_uid' => $uid,
                'employee_id' => $employee->id,
            ]);

            $data[] = ['client_uid' => $lowercaseCsvValue ? strtolower($uid) : $uid];
        }

        return $data;
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @throws Throwable
     * @return array
     */
    private function seedExistingVouchersAndBuildCsvRowsForBsn(Fund $fund, int $count): array
    {
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
    private function seedExistingVouchersAndBuildCsvRowsForEmail(Fund $fund, int $count): array
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
    private function seedExistingVouchersAndBuildCsvRowsForAllDuplicateTypes(Fund $fund, int $count): array
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
