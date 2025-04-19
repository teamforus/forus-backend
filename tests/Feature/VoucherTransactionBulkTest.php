<?php

namespace Tests\Feature;

use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesVoucherTransaction;

class VoucherTransactionBulkTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesVoucherTransaction;
    use MakesTestBankConnections;

    /**
     * @return void
     */
    public function testVoucherTransactionBulkCreate(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);

        $this->makeTransactions($fund);
        $this->makeBankConnection($organization);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->postJson(
            uri: "/api/v1/platform/organizations/$organization->id/sponsor/transaction-bulks",
            headers: $apiHeaders
        );

        $response->assertSuccessful();

        $bulk = VoucherTransactionBulk::where('id', $response->json('data.0.id'))->first();
        $this->assertNotNull($bulk);
    }

    /**
     * @return void
     */
    public function testVoucherTransactionBulkCreateByCommand(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $organization->forceFill([
            'bank_cron_time' => now(),
        ])->save();

        $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);
        $this->makeTransactions($fund);
        $connection = $this->makeBankConnection($organization);

        Artisan::call('bank:bulks-build');

        $bulk = VoucherTransactionBulk::where('bank_connection_id', $connection->id)->first();
        $this->assertNotNull($bulk);
    }

    /**
     * @return void
     */
    public function testVoucherTransactionMeta(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);

        // make paid transactions, so they must not be included in total amount for bulk
        $paidTransactions = $this
            ->makeTransactions($fund)
            ->each(fn (VoucherTransaction $t) => $t->setPaid(null, now()));

        $transactions = $this->makeTransactions($fund);
        $this->makeBankConnection($organization);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // assert meta totals
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/transactions",
            $apiHeaders
        );

        $response->assertSuccessful();

        $this->assertSame(
            currency_format($transactions->sum('amount') + $paidTransactions->sum('amount')),
            $response->json('meta.total_amount')
        );

        $this->assertSame(
            currency_format_locale($transactions->sum('amount') + $paidTransactions->sum('amount')),
            $response->json('meta.total_amount_locale')
        );

        // assert meta totals for pending bulking
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/sponsor/transactions?pending_bulking=1",
            $apiHeaders
        );

        $response->assertSuccessful();
        $this->assertEquals($transactions->count(), $response->json('meta.total'));
        $this->assertSame(currency_format($transactions->sum('amount')), $response->json('meta.total_amount'));
        $this->assertSame(currency_format_locale($transactions->sum('amount')), $response->json('meta.total_amount_locale'));
    }

    /**
     * @return void
     */
    public function testVoucherTransactionExportSepa(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $organization->forceFill([
            'allow_manual_bulk_processing' => false,
        ])->save();

        $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);
        $this->makeTransactions($fund);
        $this->makeBankConnection($organization);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->postJson(
            uri: "/api/v1/platform/organizations/$organization->id/sponsor/transaction-bulks",
            headers: $apiHeaders
        );

        $response->assertSuccessful();

        $bulk = VoucherTransactionBulk::where('id', $response->json('data.0.id'))->first();
        $this->assertNotNull($bulk);

        // assert forbidden when try to export sepa without organization flag "allow_manual_bulk_processing"
        $this->get(
            "/api/v1/platform/organizations/$organization->id/sponsor/transaction-bulks/$bulk->id/export-sepa",
            $apiHeaders
        )->assertForbidden();

        // assert success when all flags are right
        $organization->forceFill([
            'allow_manual_bulk_processing' => true,
        ])->save();

        $this->get(
            "/api/v1/platform/organizations/$organization->id/sponsor/transaction-bulks/$bulk->id/export-sepa",
            $apiHeaders
        )->assertSuccessful();

        $this->assertTrue($bulk->refresh()->is_exported);
    }

    /**
     * @return void
     */
    public function testVoucherTransactionSetAccepted(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $organization->forceFill([
            'allow_manual_bulk_processing' => true,
        ])->save();

        $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);
        $this->makeTransactions($fund);
        $this->makeBankConnection($organization);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->postJson(
            uri: "/api/v1/platform/organizations/$organization->id/sponsor/transaction-bulks",
            headers: $apiHeaders
        );

        $response->assertSuccessful();

        $bulk = VoucherTransactionBulk::where('id', $response->json('data.0.id'))->first();
        $this->assertNotNull($bulk);

        // export sepa and set accepted
        $this->patch(
            uri: "/api/v1/platform/organizations/$organization->id/sponsor/transaction-bulks/$bulk->id/set-accepted",
            headers: $apiHeaders
        )->assertForbidden();

        $this->get(
            "/api/v1/platform/organizations/$organization->id/sponsor/transaction-bulks/$bulk->id/export-sepa",
            $apiHeaders
        )->assertSuccessful();

        $this->patch(
            uri: "/api/v1/platform/organizations/$organization->id/sponsor/transaction-bulks/$bulk->id/set-accepted",
            headers: $apiHeaders
        )->assertSuccessful();

        // assert accepted_manually and logs
        $this->assertTrue($bulk->fresh()->accepted_manually);
        $logs = $bulk->logs()->where('event', VoucherTransactionBulk::EVENT_ACCEPTED_MANUALLY)->get();

        $this->assertEquals(
            1,
            $logs->count(),
            'Event accepted manually must be created',
        );

        $this->assertEquals($organization->employees[0]->id, $logs[0]->data['employee_id']);
    }
}
