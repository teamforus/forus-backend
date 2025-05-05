<?php

namespace Tests\Feature;

use App\Events\Vouchers\VoucherExpireSoon;
use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Mail\Funds\FundRequests\FundRequestDisregardedMail;
use App\Mail\ProductReservations\ProductReservationAcceptedMail;
use App\Mail\ProductReservations\ProductReservationCanceledMail;
use App\Mail\ProductReservations\ProductReservationRejectedMail;
use App\Mail\Reimbursements\ReimbursementApprovedMail;
use App\Mail\Reimbursements\ReimbursementDeclinedMail;
use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Mail\Vouchers\DeactivationVoucherMail;
use App\Mail\Vouchers\PaymentSuccessBudgetMail;
use App\Mail\Vouchers\RequestPhysicalCardMail;
use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Mail\Vouchers\VoucherAssignedProductMail;
use App\Mail\Vouchers\VoucherAssignedSubsidyMail;
use App\Mail\Vouchers\VoucherExpireSoonBudgetMail;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestReimbursements;
use Throwable;

class EmailLogTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesTestReimbursements;
    use MakesProductReservations;

    protected ?Carbon $startTime = null;

    /**
     * @return void
     */
    public function testVoucherAssignedBudgetMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $this->makeTestFund($organization)->makeVoucher($identity);
        $this->assertLogExists($identity, $organization, VoucherAssignedBudgetMail::class);
    }

    /**
     * @return void
     */
    public function testVoucherAssignedProductMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund = $this->makeTestFund($organization);
        $product = $this->makeProductsFundFund(1)[0];

        $this->addProductFundToFund($fund, $product, false);
        $fund->makeVoucher($identity)->buyProductVoucher($product);
        $this->assertLogExists($identity, $organization, VoucherAssignedProductMail::class);
    }

    /**
     * @return void
     */
    public function testVoucherAssignedSubsidyMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $this->makeTestSubsidyFund($organization)->makeVoucher($identity);
        $this->assertLogExists($identity, $organization, VoucherAssignedSubsidyMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDeactivationVoucherMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $this->makeTestFund($organization)->makeVoucher($identity)->deactivate(notifyByEmail: true);
        $this->assertLogExists($identity, $organization, DeactivationVoucherMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherExpireSoonBudgetMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestFund($this->makeTestOrganization($identity))->makeVoucher($identity);

        VoucherExpireSoon::dispatch($voucher);

        $this->assertLogExists($identity, $voucher->fund->organization, VoucherExpireSoonBudgetMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequestPhysicalCardMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestFund($this->makeTestOrganization($identity))->makeVoucher($identity);

        $voucher->makePhysicalCardRequest([
            'address' => $this->faker->streetAddress,
            'house' => $this->faker->buildingNumber,
            'house_addition' => $this->faker->buildingNumber,
            'postcode' => $this->faker->postcode,
            'city' => $this->faker->city,
        ], true);

        $this->assertLogExists($identity, $voucher->fund->organization, RequestPhysicalCardMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPaymentSuccessBudgetMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $voucher = $this->makeTestFund($organization)->makeVoucher($identity);
        $product = $this->makeProductsFundFund(1)[0];
        $employee = $organization->employees[0];

        $this->addProductFundToFund($voucher->fund, $product, false);

        $transaction = $voucher->makeTransaction([
            'amount' => $voucher->amount,
            'product_id' => $voucher->product_id,
            'employee_id' => $employee?->id,
            'branch_id' => $employee?->office?->branch_id,
            'branch_name' => $employee?->office?->branch_name,
            'branch_number' => $employee?->office?->branch_number,
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'organization_id' => $product->organization_id,
        ]);

        $transaction->setPaid(null, now());

        VoucherTransactionCreated::dispatch($transaction);

        $this->assertLogExists($identity, $organization, PaymentSuccessBudgetMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationAcceptedMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $this->makeTestFund($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $reservation = $this->makeReservation($voucher, $product);
        $reservation->acceptProvider();

        $this->assertLogExists($identity, $organization, ProductReservationAcceptedMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationCanceledMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $this->makeTestFund($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->makeReservation($voucher, $product)->acceptProvider()->rejectOrCancelProvider();
        $this->assertLogExists($identity, $organization, ProductReservationCanceledMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationRejectedMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $this->makeTestFund($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->makeReservation($voucher, $product)->rejectOrCancelProvider();
        $this->assertLogExists($identity, $organization, ProductReservationRejectedMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementSubmittedMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $voucher = $this->makeTestFund($organization, [], [
            'allow_reimbursements' => true,
        ])->makeVoucher($identity);

        $this->makeReimbursement($voucher, true);
        $this->assertLogExists($identity, $organization, ReimbursementSubmittedMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementApprovedMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $voucher = $this->makeTestFund($organization, [], [
            'allow_reimbursements' => true,
        ])->makeVoucher($identity);

        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        $this->assignReimbursementInDashboard($reimbursement, $employee, true);
        $this->resolveReimbursementInDashboard($reimbursement, $employee, true, true);

        $this->assertLogExists($identity, $organization, ReimbursementApprovedMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementDeclinedMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $voucher = $this->makeTestFund($organization, [], [
            'allow_reimbursements' => true,
        ])->makeVoucher($identity);

        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        $this->assignReimbursementInDashboard($reimbursement, $employee, true);
        $this->resolveReimbursementInDashboard($reimbursement, $employee, false, true);

        $this->assertLogExists($identity, $organization, ReimbursementDeclinedMail::class);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCreatedMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund = $this->makeTestFund($organization, [], [
            'allow_fund_requests' => true,
        ]);

        $fundRequest = $this->setCriteriaAndMakeFundRequest($identity, $fund, [
            'children_nth' => 3,
        ]);

        $this->assertLogExists($identity, $organization, FundRequestCreatedMail::class);
        $this->assertLogExists($identity, $organization, FundRequestCreatedMail::class, $fundRequest);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestApprovedMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund = $this->makeTestFund($organization, [], [
            'allow_fund_requests' => true,
        ]);

        $fundRequest = $this->setCriteriaAndMakeFundRequest($identity, $fund, [
            'children_nth' => 3,
        ]);

        $this->approveFundRequest($fundRequest);

        $this->assertLogExists($identity, $organization, FundRequestApprovedMail::class);
        $this->assertLogExists($identity, $organization, FundRequestApprovedMail::class, $fundRequest);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestDisregardedMailLog(): void
    {
        $this->startTime = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund = $this->makeTestFund($organization, [], [
            'allow_fund_requests' => true,
        ]);

        $fundRequest = $this->setCriteriaAndMakeFundRequest($identity, $fund, [
            'children_nth' => 3,
        ]);

        $this->disregardFundRequest($fundRequest);

        $this->assertLogExists($identity, $organization, FundRequestDisregardedMail::class);
        $this->assertLogExists($identity, $organization, FundRequestDisregardedMail::class, $fundRequest);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestDeniedMailLog(): void
    {
        $this->startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization, [], [
            'allow_fund_requests' => true,
        ]);

        $fundRequest = $this->setCriteriaAndMakeFundRequest($identity, $fund, [
            'children_nth' => 3,
        ]);

        $this->declineFundRequest($fundRequest);

        $this->assertLogExists($identity, $organization, FundRequestDeniedMail::class);
        $this->assertLogExists($identity, $organization, FundRequestDeniedMail::class, $fundRequest);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestClarificationRequestedMailLog(): void
    {
        $this->startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization, [], [
            'allow_fund_requests' => true,
        ]);

        $fundRequest = $this->setCriteriaAndMakeFundRequest($identity, $fund, [
            'children_nth' => 3,
        ]);

        $this->requestFundRequestClarification($organization, $fundRequest, [
            'Accept' => 'application/json',
            'client_type' => 'webshop',
        ]);

        $this->assertLogExists($identity, $organization, FundRequestClarificationRequestedMail::class);
        $this->assertLogExists($identity, $organization, FundRequestClarificationRequestedMail::class, $fundRequest);
    }

    /**
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function approveFundRequest(FundRequest $fundRequest): void
    {
        $employee = $fundRequest->fund->organization->employees[0];
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->refresh();

        $fundRequest->approve();
        $fundRequest->refresh();
    }

    /**
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function disregardFundRequest(FundRequest $fundRequest): void
    {
        $employee = $fundRequest->fund->organization->employees[0];
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->refresh();

        $fundRequest->disregard(notify: true);
        $fundRequest->refresh();
    }

    /**
     * @param FundRequest $fundRequest
     * @throws Throwable
     * @return void
     */
    protected function declineFundRequest(FundRequest $fundRequest): void
    {
        $employee = $fundRequest->fund->organization->employees[0];
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->refresh();

        $fundRequest->decline();
        $fundRequest->refresh();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param string $mailable
     * @param FundRequest|null $fundRequest
     * @return void
     */
    protected function assertLogExists(
        Identity $identity,
        Organization $organization,
        string $mailable,
        ?FundRequest $fundRequest = null,
    ): void {
        $headers = $this->makeApiHeaders($organization->identity);
        $this->assertMailableSent($identity->email, $mailable, $this->startTime);

        /** @var EmailLog $log */
        $log = $this->getEmailOfTypeQuery($identity->email, $mailable, $this->startTime)->first();
        $this->assertNotNull($log);

        // assert at least one filter fund_request_id or identity_id is required
        $this->getJson(
            "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query([]),
            $headers,
        )->assertForbidden();

        // assert successfully found log
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query(
                $fundRequest ? ['fund_request_id' => $fundRequest->id] : ['identity_id' => $identity->id]
            ),
            $headers,
        );

        $response->assertSuccessful();
        $exists = array_filter($response->json('data'), fn ($item) => $item['id'] === $log->id);
        $this->assertNotEmpty($exists, "No $log->mailable log found");

        // assert export
        $this->getJson(
            "/api/v1/platform/organizations/$organization->id/email-logs/$log->id/export",
            $headers,
        )->assertSuccessful();

        // assert access for other cases
        if ($fundRequest) {
            // assert that employee doesn't have access to logs for other fund_request (not related to organization)
            $otherOrganization = $this->makeTestOrganization($this->makeIdentity());
            $otherFund = $this->makeTestFund($otherOrganization);
            $otherIdentity = $this->makeIdentity($this->makeUniqueEmail());

            $otherFundRequest = $this->setCriteriaAndMakeFundRequest($otherIdentity, $otherFund, [
                'children_nth' => 3,
            ]);

            $this->getJson(
                "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query([
                    'fund_request_id' => $otherFundRequest->id,
                ]),
                $headers,
            )->assertForbidden();

            // assert that employee doesn't see log for other fund_request (related to organization)
            $otherIdentity = $this->makeIdentity($this->makeUniqueEmail());
            $otherFundRequest = $this->setCriteriaAndMakeFundRequest($otherIdentity, $organization->funds[0], [
                'children_nth' => 3,
            ]);

            $response = $this->getJson(
                "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query([
                    'fund_request_id' => $otherFundRequest->id,
                ]),
                $headers,
            );

            $response->assertSuccessful();
            $exists = array_filter($response->json('data'), fn ($item) => $item['id'] === $log->id);
            $this->assertEmpty($exists, "$log->mailable is visible for other fund request");
        } else {
            // assert that employee doesn't have access to other identity logs (not related to organization)
            $otherIdentity = $this->makeIdentity($this->makeUniqueEmail());

            $this->getJson(
                "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query([
                    'identity_id' => $otherIdentity->id,
                ]),
                $headers,
            )->assertForbidden();

            // assert that employee doesn't see log for other identity (related to organization)
            $organization->funds[0]->makeVoucher($otherIdentity);

            $response = $this->getJson(
                "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query([
                    'identity_id' => $otherIdentity->id,
                ]),
                $headers,
            );

            $response->assertSuccessful();
            $exists = array_filter($response->json('data'), fn ($item) => $item['id'] === $log->id);
            $this->assertEmpty($exists, "$log->mailable is visible for other identity");
        }
    }
}
