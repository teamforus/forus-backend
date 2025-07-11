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
use App\Mail\Vouchers\VoucherExpireSoonBudgetMail;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\EmailLogQuery;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestReimbursements;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class EmailLogTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestVouchers;
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
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity2);

        $this->makeTestVoucher($this->makeTestFund($organization1), $identity);
        $this->makeTestVoucher($this->makeTestFund($organization2), $identity);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: VoucherAssignedBudgetMail::class,
        );
    }

    /**
     * @return void
     */
    public function testVoucherAssignedProductMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $fund1 = $this->makeTestFund($organization1);
        $fund2 = $this->makeTestFund($organization2);

        $product1 = $this->makeProductsFundFund(1)[0];
        $product2 = $this->makeProductsFundFund(1)[0];

        $this->addProductFundToFund($fund1, $product1, false);
        $this->addProductFundToFund($fund2, $product2, false);

        $this->makeTestVoucher($fund1, $identity)->buyProductVoucher($product1);
        $this->makeTestVoucher($fund2, $identity)->buyProductVoucher($product2);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: VoucherAssignedProductMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDeactivationVoucherMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $this->makeTestVoucher($this->makeTestFund($organization1), $identity)->deactivate(notifyByEmail: true);
        $this->makeTestVoucher($this->makeTestFund($organization2), $identity)->deactivate(notifyByEmail: true);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: DeactivationVoucherMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherExpireSoonBudgetMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1), $identity);
        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2), $identity);

        VoucherExpireSoon::dispatch($voucher1);
        VoucherExpireSoon::dispatch($voucher2);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: VoucherExpireSoonBudgetMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequestPhysicalCardMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1), $identity);
        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2), $identity);

        $voucher1->makePhysicalCardRequest([
            'address' => $this->faker->streetAddress,
            'house' => $this->faker->buildingNumber,
            'house_addition' => $this->faker->buildingNumber,
            'postcode' => $this->faker->postcode,
            'city' => $this->faker->city,
        ], true);

        $voucher2->makePhysicalCardRequest([
            'address' => $this->faker->streetAddress,
            'house' => $this->faker->buildingNumber,
            'house_addition' => $this->faker->buildingNumber,
            'postcode' => $this->faker->postcode,
            'city' => $this->faker->city,
        ], true);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: RequestPhysicalCardMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPaymentSuccessBudgetMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1), $identity);
        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2), $identity);

        $product1 = $this->makeProductsFundFund(1)[0];
        $product2 = $this->makeProductsFundFund(1)[0];

        $employee1 = $organization1->employees[0];
        $employee2 = $organization2->employees[0];

        $this->addProductFundToFund($voucher1->fund, $product1, false);
        $this->addProductFundToFund($voucher2->fund, $product2, false);

        $transaction1 = $voucher1->makeTransaction([
            'amount' => $voucher1->amount,
            'product_id' => $voucher1->product_id,
            'employee_id' => $employee1?->id,
            'branch_id' => $employee1?->office?->branch_id,
            'branch_name' => $employee1?->office?->branch_name,
            'branch_number' => $employee1?->office?->branch_number,
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'organization_id' => $product1->organization_id,
        ]);

        $transaction2 = $voucher2->makeTransaction([
            'amount' => $voucher2->amount,
            'product_id' => $voucher2->product_id,
            'employee_id' => $employee2?->id,
            'branch_id' => $employee2?->office?->branch_id,
            'branch_name' => $employee2?->office?->branch_name,
            'branch_number' => $employee2?->office?->branch_number,
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'organization_id' => $product2->organization_id,
        ]);

        $transaction1->setPaid(null, now());
        $transaction2->setPaid(null, now());

        VoucherTransactionCreated::dispatch($transaction1);
        VoucherTransactionCreated::dispatch($transaction2);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: PaymentSuccessBudgetMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationAcceptedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1), identity: $identity2);
        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2), identity: $identity2);

        $product1 = $this->findProductForReservation($voucher1);
        $product2 = $this->findProductForReservation($voucher2);

        $this->makeReservation($voucher1, $product1)->acceptProvider();
        $this->makeReservation($voucher2, $product2)->acceptProvider();

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity2,
            organization1: $organization1,
            organization2: $organization2,
            mailable: ProductReservationAcceptedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationCanceledMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1), identity: $identity2);
        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2), identity: $identity2);

        $product1 = $this->findProductForReservation($voucher1);
        $product2 = $this->findProductForReservation($voucher2);

        $this->makeReservation($voucher1, $product1)->acceptProvider()->rejectOrCancelProvider();
        $this->makeReservation($voucher2, $product2)->acceptProvider()->rejectOrCancelProvider();

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity2,
            organization1: $organization1,
            organization2: $organization2,
            mailable: ProductReservationCanceledMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationRejectedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1), identity: $identity2);
        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2), identity: $identity2);

        $this->makeReservation($voucher1, $this->findProductForReservation($voucher1))->rejectOrCancelProvider();
        $this->makeReservation($voucher2, $this->findProductForReservation($voucher2))->rejectOrCancelProvider();

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity2,
            organization1: $organization1,
            organization2: $organization2,
            mailable: ProductReservationRejectedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementSubmittedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1, [], [
            'allow_reimbursements' => true,
        ]), $identity);

        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2, [], [
            'allow_reimbursements' => true,
        ]), $identity);

        $this->makeReimbursement($voucher1, true);
        $this->makeReimbursement($voucher2, true);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: ReimbursementSubmittedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementApprovedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1, [], [
            'allow_reimbursements' => true,
        ]), $identity);

        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2, [], [
            'allow_reimbursements' => true,
        ]), $identity);

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $employee1 = $this->makeReimbursementValidatorEmployee($reimbursement1);
        $employee2 = $this->makeReimbursementValidatorEmployee($reimbursement2);

        $this->assignReimbursementInDashboard($reimbursement1, $employee1, true);
        $this->assignReimbursementInDashboard($reimbursement2, $employee2, true);

        $this->resolveReimbursementInDashboard($reimbursement1, $employee1, true, true);
        $this->resolveReimbursementInDashboard($reimbursement2, $employee2, true, true);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: ReimbursementApprovedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementDeclinedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $voucher1 = $this->makeTestVoucher($this->makeTestFund($organization1, [], ['allow_reimbursements' => true]), $identity);
        $voucher2 = $this->makeTestVoucher($this->makeTestFund($organization2, [], ['allow_reimbursements' => true]), $identity);

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $employee1 = $this->makeReimbursementValidatorEmployee($reimbursement1);
        $employee2 = $this->makeReimbursementValidatorEmployee($reimbursement2);

        $this->assignReimbursementInDashboard($reimbursement1, $employee1, true);
        $this->assignReimbursementInDashboard($reimbursement2, $employee2, true);

        $this->resolveReimbursementInDashboard($reimbursement1, $employee1, false, true);
        $this->resolveReimbursementInDashboard($reimbursement2, $employee2, false, true);

        $this->assertIdentityEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            organization1: $organization1,
            organization2: $organization2,
            mailable: ReimbursementDeclinedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestCreatedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $fund1 = $this->makeTestFund($organization1, [], ['allow_fund_requests' => true]);
        $fund2 = $this->makeTestFund($organization2, [], ['allow_fund_requests' => true]);

        $fundRequest1 = $this->setCriteriaAndMakeFundRequest($identity, $fund1, ['children_nth' => 3]);
        $fundRequest2 = $this->setCriteriaAndMakeFundRequest($identity, $fund2, ['children_nth' => 3]);

        $this->assertFundRequestEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            fundRequest1: $fundRequest1,
            fundRequest2: $fundRequest2,
            organization1: $organization1,
            organization2: $organization2,
            mailable: FundRequestCreatedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestApprovedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $fund1 = $this->makeTestFund($organization1, [], ['allow_fund_requests' => true]);
        $fund2 = $this->makeTestFund($organization2, [], ['allow_fund_requests' => true]);

        $fundRequest1 = $this->setCriteriaAndMakeFundRequest($identity, $fund1, ['children_nth' => 3]);
        $fundRequest2 = $this->setCriteriaAndMakeFundRequest($identity, $fund2, ['children_nth' => 3]);

        $this->approveFundRequest($fundRequest1);
        $this->approveFundRequest($fundRequest2);

        $this->assertFundRequestEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            fundRequest1: $fundRequest1,
            fundRequest2: $fundRequest2,
            organization1: $organization1,
            organization2: $organization2,
            mailable: FundRequestApprovedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestDisregardedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $fund1 = $this->makeTestFund($organization1, [], ['allow_fund_requests' => true]);
        $fund2 = $this->makeTestFund($organization2, [], ['allow_fund_requests' => true]);

        $fundRequest1 = $this->setCriteriaAndMakeFundRequest($identity, $fund1, ['children_nth' => 3]);
        $fundRequest2 = $this->setCriteriaAndMakeFundRequest($identity, $fund2, ['children_nth' => 3]);

        $this->disregardFundRequest($fundRequest1);
        $this->disregardFundRequest($fundRequest2);

        $this->assertFundRequestEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            fundRequest1: $fundRequest1,
            fundRequest2: $fundRequest2,
            organization1: $organization1,
            organization2: $organization2,
            mailable: FundRequestDisregardedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestDeniedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $fund1 = $this->makeTestFund($organization1, [], ['allow_fund_requests' => true]);
        $fund2 = $this->makeTestFund($organization2, [], ['allow_fund_requests' => true]);

        $fundRequest1 = $this->setCriteriaAndMakeFundRequest($identity, $fund1, ['children_nth' => 3]);
        $fundRequest2 = $this->setCriteriaAndMakeFundRequest($identity, $fund2, ['children_nth' => 3]);

        $this->declineFundRequest($fundRequest1);
        $this->declineFundRequest($fundRequest2);

        $this->assertFundRequestEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            fundRequest1: $fundRequest1,
            fundRequest2: $fundRequest2,
            organization1: $organization1,
            organization2: $organization2,
            mailable: FundRequestDeniedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestClarificationRequestedMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $organization1 = $this->makeTestOrganization($identity);
        $organization2 = $this->makeTestOrganization($identity);

        $fund1 = $this->makeTestFund($organization1, [], ['allow_fund_requests' => true]);
        $fund2 = $this->makeTestFund($organization2, [], ['allow_fund_requests' => true]);

        $fundRequest1 = $this->setCriteriaAndMakeFundRequest($identity, $fund1, ['children_nth' => 3]);
        $fundRequest2 = $this->setCriteriaAndMakeFundRequest($identity, $fund2, ['children_nth' => 3]);

        $this->requestFundRequestClarification($organization1, $fundRequest1);
        $this->requestFundRequestClarification($organization2, $fundRequest2);

        $this->assertFundRequestEmailLogVisibilityForOrganizations(
            startTime: $startTime,
            identity: $identity,
            fundRequest1: $fundRequest1,
            fundRequest2: $fundRequest2,
            organization1: $organization1,
            organization2: $organization2,
            mailable: FundRequestClarificationRequestedMail::class,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmailLogsEndpointRequireFilters(): void
    {
        $this->makeEmailLogsRequest($this->makeTestOrganization($this->makeIdentity()), [])->assertForbidden();
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
     * @param ?Organization $organization
     * @param string $mailable
     * @param Carbon $startTime
     * @return EmailLog[]|Collection
     */
    protected function findIdentityEmailLog(
        Identity $identity,
        ?Organization $organization,
        string $mailable,
        Carbon $startTime,
    ): Collection|array {
        return EmailLogQuery::whereIdentity(EmailLog::query(), $identity, $organization)
            ->where('mailable', $mailable)
            ->where('created_at', '>=', $startTime)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param Carbon $startTime
     * @param Identity $identity
     * @param Organization $organization1
     * @param Organization $organization2
     * @param string $mailable
     * @return void
     */
    protected function assertIdentityEmailLogVisibilityForOrganizations(
        Carbon $startTime,
        Identity $identity,
        Organization $organization1,
        Organization $organization2,
        string $mailable,
    ): void {
        $logs = $this->findIdentityEmailLog($identity, null, $mailable, $startTime);
        $this->assertCount(2, $logs);

        $this->assertIdentityEmailLogVisibilityForOrganization(
            $identity,
            $organization1,
            logsVisible: $logs->slice(0, 1)->values(),
            logsNotVisible: $logs->slice(1, 1)->values(),
        );

        $this->assertIdentityEmailLogVisibilityForOrganization(
            $identity,
            $organization2,
            logsVisible: $logs->slice(1, 1)->values(),
            logsNotVisible: $logs->slice(0, 1)->values()
        );
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param Collection|EmailLog $logsVisible
     * @param Collection|EmailLog $logsNotVisible
     * @return void
     */
    protected function assertIdentityEmailLogVisibilityForOrganization(
        Identity $identity,
        Organization $organization,
        Collection|EmailLog $logsVisible,
        Collection|EmailLog $logsNotVisible,
    ): void {
        $logsVisibleIds = $logsVisible->pluck('id')->toArray();
        $logsNotVisibleIds = $logsNotVisible->pluck('id')->toArray();

        $this->makeEmailLogsRequest($organization, ['identity_id' => $identity->id])
            ->assertSuccessful()
            ->assertJsonPath('data', function (array $data) use ($logsVisibleIds, $logsNotVisibleIds) {
                $ids = array_pluck($data, 'id');

                foreach ($logsVisibleIds as $id) {

                    if (!in_array($id, $ids)) {
                        return false;
                    }
                }

                foreach ($logsNotVisibleIds as $id) {
                    if (in_array($id, $ids)) {
                        return false;
                    }
                }

                return true;
            });

        // assert export
        foreach ($logsVisible as $log) {
            $this->makeEmailLogsExportRequest($organization, $log)->assertSuccessful();
        }

        foreach ($logsNotVisible as $log) {
            $this->makeEmailLogsExportRequest($organization, $log)->assertForbidden();
        }
    }

    /**
     * @param Carbon $startTime
     * @param Identity $identity
     * @param FundRequest $fundRequest1
     * @param FundRequest $fundRequest2
     * @param Organization $organization1
     * @param Organization $organization2
     * @param string $mailable
     * @return void
     */
    protected function assertFundRequestEmailLogVisibilityForOrganizations(
        Carbon $startTime,
        Identity $identity,
        FundRequest $fundRequest1,
        FundRequest $fundRequest2,
        Organization $organization1,
        Organization $organization2,
        string $mailable,
    ): void {
        $logs = $this->findIdentityEmailLog($identity, null, $mailable, $startTime);
        $this->assertCount(2, $logs);

        $this->assertFundRequestEmailLogVisibilityForOrganization(
            $fundRequest1,
            $organization1,
            logsVisible: $logs->slice(0, 1)->values(),
            logsNotVisible: $logs->slice(1, 1)->values(),
        );

        $this->assertFundRequestEmailLogVisibilityForOrganization(
            $fundRequest2,
            $organization2,
            logsVisible: $logs->slice(1, 1)->values(),
            logsNotVisible: $logs->slice(0, 1)->values()
        );
    }

    /**
     * @param FundRequest $fundRequest
     * @param Organization $organization
     * @param Collection|EmailLog $logsVisible
     * @param Collection|EmailLog $logsNotVisible
     * @return void
     */
    protected function assertFundRequestEmailLogVisibilityForOrganization(
        FundRequest $fundRequest,
        Organization $organization,
        Collection|EmailLog $logsVisible,
        Collection|EmailLog $logsNotVisible,
    ): void {
        $logsVisibleIds = $logsVisible->pluck('id')->toArray();
        $logsNotVisibleIds = $logsNotVisible->pluck('id')->toArray();

        $this->makeEmailLogsRequest($organization, ['fund_request_id' => $fundRequest->id])
            ->assertSuccessful()
            ->assertJsonPath('data', function (array $data) use ($logsVisibleIds, $logsNotVisibleIds) {
                $ids = array_pluck($data, 'id');

                foreach ($logsVisibleIds as $id) {

                    if (!in_array($id, $ids)) {
                        return false;
                    }
                }

                foreach ($logsNotVisibleIds as $id) {
                    if (in_array($id, $ids)) {
                        return false;
                    }
                }

                return true;
            });

        // assert export
        foreach ($logsVisible as $log) {
            $this->makeEmailLogsExportRequest($organization, $log)->assertSuccessful();
        }

        foreach ($logsNotVisible as $log) {
            $this->makeEmailLogsExportRequest($organization, $log)->assertForbidden();
        }
    }

    /**
     * Makes a request to fetch email logs for a given organization.
     *
     * @param Organization $organization
     * @param array $params
     * @return TestResponse
     */
    protected function makeEmailLogsRequest(Organization $organization, array $params): TestResponse
    {
        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/email-logs?" . http_build_query($params),
            $this->makeApiHeaders($organization->identity),
        );
    }

    /**
     * Makes an API request to export email logs for a given organization and log.
     *
     * @param Organization $organization
     * @param EmailLog $log
     * @return TestResponse
     */
    protected function makeEmailLogsExportRequest(Organization $organization, EmailLog $log): TestResponse
    {

        return $this->getJson(
            "/api/v1/platform/organizations/$organization->id/email-logs/$log->id/export",
            $this->makeApiHeaders($organization->identity),
        );
    }
}
