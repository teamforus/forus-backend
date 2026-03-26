<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\Implementation;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VoucherCreateDuplicatesTest extends DuskTestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use NavigatesFrontendDashboard;

    /**
     * @throws Throwable
     */
    public function testCreateVoucherShowsEmailDuplicatePicker(): void
    {
        $this->runCreateVoucherDuplicatePickerFlow('email', $this->makeUniqueEmail());
    }

    /**
     * @throws Throwable
     */
    public function testCreateVoucherShowsBsnDuplicatePicker(): void
    {
        $this->runCreateVoucherDuplicatePickerFlow('bsn', (string) $this->randomFakeBsn());
    }

    /**
     * @throws Throwable
     */
    public function testCreateVoucherShowsClientUidDuplicatePicker(): void
    {
        $this->runCreateVoucherDuplicatePickerFlow('client_uid', 'CLIENT-UID-1');
    }

    /**
     * @param string $type
     * @param string $value
     * @throws Throwable
     */
    protected function runCreateVoucherDuplicatePickerFlow(string $type, string $value): void
    {
        $implementation = Implementation::general();
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);
        $fund = $this->makeTestFund($organization);

        $this->seedDuplicateVoucher($fund, $type, $value);

        $this->rollbackModels([], function () use ($fund, $implementation, $type, $value) {
            $this->browse(function (Browser $browser) use ($fund, $implementation, $type, $value) {
                $identity = $fund->organization->identity;

                $browser->visit($implementation->urlSponsorDashboard());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $fund->organization);
                $this->goToVouchersPage($browser);

                $browser->element('#create_voucher')->click();
                $browser->waitFor('@modalVoucherCreate');
                $browser->type('@voucherCreateAmount', '10');

                if ($type === 'client_uid') {
                    $browser->type('@voucherCreateClientUid', $value);
                } else {
                    $this->changeSelectControl(
                        $browser,
                        '@voucherCreateAssignType',
                        $type === 'email' ? 'Via e-mail' : 'Via BSN',
                    );
                    $browser->type('@voucherCreateAssignInput', $value);
                }

                $browser->element('@voucherCreateSubmit')->click();
                $browser->waitFor('@modalDuplicatesPicker');
                $browser->waitForTextIn('@duplicateItem', $value);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @param string $type
     * @param string $value
     * @throws Throwable
     */
    protected function seedDuplicateVoucher(Fund $fund, string $type, string $value): void
    {
        $employee = $fund->organization->employees()->first();

        if ($type === 'email') {
            $identity = $this->makeIdentity(email: $value);
            $this->makeTestVoucher($fund, $identity, voucherFields: ['employee_id' => $employee->id]);
            return;
        }

        if ($type === 'bsn') {
            $identity = $this->makeIdentity(bsn: $value);
            $this->makeTestVoucher($fund, $identity, voucherFields: ['employee_id' => $employee->id]);
            return;
        }

        $this->makeTestVoucher($fund, voucherFields: [
            'client_uid' => $value,
            'employee_id' => $employee->id,
        ]);
    }
}
