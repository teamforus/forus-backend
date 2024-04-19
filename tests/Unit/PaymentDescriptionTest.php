<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesOrganizationOffice;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestTransaction;
use Throwable;

class PaymentDescriptionTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication,
        MakesTestOrganizations, MakesTestTransaction,
        MakesTestFunds, MakesOrganizationOffice, MakesProductReservations;

    public function testDirectTransactionPaymentDescription()
    {
        $transaction = $this->makeVoucherTransaction();

        //- Check transactions with target 'iban'
        $transaction->update([
            'target' => 'iban'
        ]);

        self::assertEquals("", $transaction->makePaymentDescription());
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testPaymentWithoutProviderDescription(): void
    {
        $transaction = $this->makeVoucherTransaction();

        //- No provider set
        $transaction->update([
            'organization_id' => null,
        ]);

        self::assertEquals("", $transaction->makePaymentDescription());
    }

    public function testMakePaymentWithDisabledProviderFlags(): void
    {
        $transaction = $this->makeVoucherTransaction();

        $this->updateProviderTransactionFlags($transaction->provider, []);

        self::assertEquals("", $transaction->makePaymentDescription());
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testProviderFlags(): void
    {
        $providerFlags = [
            'bank_transaction_id',
            'bank_transaction_date',
            'bank_reservation_number',
            'bank_branch_number',
            'bank_branch_id',
            'bank_branch_name',
            'bank_fund_name',
            'bank_note',
        ];

        foreach ($providerFlags as $index => $flag) {
            $this->checkPaymentDescriptionFlags(array_slice($providerFlags, 0, $index + 1));
        }
    }

    /**
     * @throws Throwable
     */
    private function checkPaymentDescriptionFlags(array $providerFlags): void
    {
        $testData = $this->getDescriptionData();

        $transaction = $this->makeVoucherTransaction();

        $this->updateEmployeeDetails($transaction->employee);

        $reservation = $this->makeBudgetReservationInDb($transaction->provider);
        $reservation->update(['voucher_transaction_id' => $transaction->id]);

        $this->updateProviderTransactionFlags($transaction->provider, $providerFlags);

        $note = $transaction->notes_provider()->create([
            'message' => $testData['bank_note'],
            'shared' => true,
        ]);

        $expectedDescription = trim(implode(' - ', array_filter([
            $transaction->provider->bank_transaction_id ? $transaction->id : null,
            $transaction->provider->bank_transaction_date ? $transaction->created_at : null,
            $transaction->provider->bank_reservation_number ? $transaction->product_reservation?->code : null,
            $transaction->provider->bank_branch_number ? $transaction->employee?->office?->branch_number : null,
            $transaction->provider->bank_branch_id ? $transaction->employee?->office?->branch_id : null,
            $transaction->provider->bank_branch_name ? $transaction->employee?->office?->branch_name : null,
            $transaction->provider->bank_fund_name ? $transaction->voucher?->fund?->name : null,
            $transaction->provider->bank_note ? $transaction->notes_provider->first()?->message : null,
        ])));

        self::assertEquals($expectedDescription, $transaction->makePaymentDescription(140));

        //- Check if payment description is limited to 140 characters
        $note->update([
            'message' => $testData['bank_note_long'],
        ]);
        $transaction->notes_provider[0]->refresh();

        self::assertEquals(140, strlen($transaction->makePaymentDescription(140)));
    }

    protected function getDescriptionData(): array
    {
        return [
            'bank_branch_number' => '123456789123',
            'bank_branch_id' => '114324234',
            'bank_branch_name' => 'JKE234',
            'bank_note' => 'Test note',
            'bank_note_long' => str_repeat('a', 141),
        ];
    }

    private function updateEmployeeDetails(Employee $employee): Employee
    {
        $testData = $this->getDescriptionData();

        $office = $this->makeOrganizationOffice($employee->organization, [
            'branch_number' => $testData['bank_branch_number'],
            'branch_name' => $testData['bank_branch_name'],
            'branch_id' => $testData['bank_branch_id'],
        ]);

        $employee->update(['office_id' => $office->id]);

        return $employee;
    }

    private function updateProviderTransactionFlags(Organization $organization, array $flags): void
    {
        $organization->update([
            'bank_transaction_id' => in_array('bank_transaction_id', $flags),
            'bank_transaction_date' => in_array('bank_transaction_id', $flags),
            'bank_reservation_number' => in_array('bank_transaction_id', $flags),
            'bank_branch_number' => in_array('bank_transaction_id', $flags),
            'bank_branch_id' => in_array('bank_transaction_id', $flags),
            'bank_branch_name' => in_array('bank_transaction_id', $flags),
            'bank_fund_name' => in_array('bank_transaction_id', $flags),
            'bank_note' => in_array('bank_transaction_id', $flags),
        ]);
    }

    private function makeVoucherTransaction(): VoucherTransaction
    {
        $organization = $this->makeOrganization();
        $fund = $this->makeTestFund($organization);
        $voucher = $fund->makeVoucher($organization->identity->address);

        return $this->makeTestTransaction($organization, $voucher);
    }

    /**
     * @return Organization
     */
    private function makeOrganization(): Organization
    {
        return $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
    }
}