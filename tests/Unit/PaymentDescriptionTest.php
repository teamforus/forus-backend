<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class PaymentDescriptionTest extends TestCase
{
    use DoesTesting;
    use DatabaseTransactions;
    use CreatesApplication;
    use MakesTestOrganizations;
    use MakesTestFunds;
    use MakesTestOrganizationOffices;
    use MakesProductReservations;

    public function testPaymentDescriptionForDirectPayments()
    {
        $transaction = $this->makeVoucherTransaction();

        //- Check transactions with target 'iban'
        $transaction->update([
            'target' => 'iban',
        ]);

        static::assertEquals(
            "$transaction->id - {$transaction->voucher->fund->name}",
            $transaction->makePaymentDescription(),
            'Bank payment description for direct payments does not match the expected.',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPaymentDescriptionWhenProviderIsMissing(): void
    {
        $transaction = $this->makeVoucherTransaction();

        //- No provider set
        $transaction->update([
            'organization_id' => null,
        ]);

        static::assertEquals(
            "$transaction->id - {$transaction->voucher->fund->name}",
            $transaction->makePaymentDescription(),
            'Bank payment description when provider is missing does not match the expected.',
        );
    }

    /**
     * @return void
     */
    public function testPaymentDescriptionWhenAllProviderFlagsDisabled(): void
    {
        $transaction = $this->makeVoucherTransaction();

        $separator = array_random(Organization::BANK_SEPARATORS);
        $this->updateProviderTransactionFlags($transaction->provider, [], $separator);

        self::assertEquals(" $separator ", $transaction->makePaymentDescription());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPaymentDescriptionProviderFlagsOneByOne(): void
    {
        $providerFlags = [
            'bank_transaction_id',
            'bank_transaction_date',
            'bank_transaction_time',
            'bank_reservation_number',
            'bank_reservation_first_name',
            'bank_reservation_last_name',
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
     * @return array
     */
    protected function getDescriptionData(): array
    {
        return [
            'bank_branch_number' => '123456789123',
            'bank_branch_id' => '114324234',
            'bank_branch_name' => 'JKE234',
            'bank_note' => 'Test note',
            'bank_note_long' => $this->faker->text(300),
        ];
    }

    /**
     * @throws Throwable
     */
    private function checkPaymentDescriptionFlags(array $providerFlags): void
    {
        $testData = $this->getDescriptionData();
        $transaction = $this->makeVoucherTransaction();

        $office = $this->makeOrganizationOffice($transaction->provider, [
            'branch_number' => $testData['bank_branch_number'],
            'branch_name' => $testData['bank_branch_name'],
            'branch_id' => $testData['bank_branch_id'],
        ]);

        $transaction->employee->update([
            'office_id' => $office->id,
        ]);

        $this->makeBudgetReservationInDb($transaction->provider)->update([
            'voucher_transaction_id' => $transaction->id,
        ]);

        $separator = array_random(Organization::BANK_SEPARATORS);
        $this->updateProviderTransactionFlags($transaction->provider, $providerFlags, $separator);

        $note = $transaction->notes_provider()->create([
            'message' => $testData['bank_note'],
            'shared' => true,
        ]);

        $separator = " $separator ";
        $expectedDescription = $separator . trim(implode($separator, array_filter([
            $transaction->provider->bank_transaction_id ? $transaction->id : null,
            $transaction->provider->bank_transaction_date ? $transaction->created_at?->format('Y-m-d') : null,
            $transaction->provider->bank_transaction_time ? $transaction->created_at?->format('H:i:s') : null,
            $transaction->provider->bank_reservation_number ? $transaction->product_reservation?->code : null,
            $transaction->provider->bank_branch_number ? $transaction->employee?->office?->branch_number : null,
            $transaction->provider->bank_branch_id ? $transaction->employee?->office?->branch_id : null,
            $transaction->provider->bank_branch_name ? $transaction->employee?->office?->branch_name : null,
            $transaction->provider->bank_fund_name ? $transaction->voucher?->fund?->name : null,
            $transaction->provider->bank_reservation_first_name ? $transaction->product_reservation?->first_name : null,
            $transaction->provider->bank_reservation_last_name ? $transaction->product_reservation?->last_name : null,
            $transaction->provider->bank_note ? $transaction->notes_provider->first()?->message : null,
        ])));

        $expectedDescription = strlen($expectedDescription) <= 140
            ? $expectedDescription
            : str_limit($expectedDescription, 140 - 3);

        self::assertEquals(
            $expectedDescription,
            $transaction->makePaymentDescription(140),
            "Payment description doesn't match the expectation."
        );

        //- Check if payment description is limited to 140 characters
        $note->update([ 'message' => $testData['bank_note_long']]);
        $transaction->notes_provider[0]->refresh();

        self::assertLessThanOrEqual(140, strlen($transaction->makePaymentDescription(140)));
    }

    /**
     * @param Organization $organization
     * @param array $flags
     * @param string $separator
     * @return void
     */
    private function updateProviderTransactionFlags(
        Organization $organization,
        array $flags,
        string $separator
    ): void {
        $organization->update([
            'bank_transaction_id' => in_array('bank_transaction_id', $flags),
            'bank_transaction_date' => in_array('bank_transaction_date', $flags),
            'bank_transaction_time' => in_array('bank_transaction_time', $flags),
            'bank_reservation_number' => in_array('bank_reservation_number', $flags),
            'bank_reservation_first_name' => in_array('bank_reservation_first_name', $flags),
            'bank_reservation_last_name' => in_array('bank_reservation_last_name', $flags),
            'bank_branch_number' => in_array('bank_branch_number', $flags),
            'bank_branch_id' => in_array('bank_branch_id', $flags),
            'bank_branch_name' => in_array('bank_branch_name', $flags),
            'bank_fund_name' => in_array('bank_fund_name', $flags),
            'bank_note' => in_array('bank_note', $flags),
            'bank_separator' => $separator,
        ]);
    }

    /**
     * @return VoucherTransaction
     */
    private function makeVoucherTransaction(): VoucherTransaction
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $employee = $organization->employees[0];
        $fund = $this->makeTestFund($organization);

        return $this->makeTestVoucher($fund)->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'employee_id' => $employee?->id,
            'branch_id' => $employee?->office?->branch_id,
            'branch_name' => $employee?->office?->branch_name,
            'branch_number' => $employee?->office?->branch_number,
            'organization_id' => $organization->id,
        ]);
    }
}
