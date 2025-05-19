<?php

namespace Tests\Feature;

use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Models\Employee;
use App\Models\Note;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\MakesTestReimbursements;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class ReimbursementTest extends TestCase
{
    use WithFaker;
    use AssertsSentEmails;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestReimbursements;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/reimbursements';

    /**
     * @throws Throwable
     * @return void
     */
    public function testStoreInvalidReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeInvalidReimbursement($this->makeTestVoucher($identity, $implementation->funds[0]));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testStoreDraftAndSubmitReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($identity, $implementation->funds[0]);

        // create draft reimbursement
        $reimbursement = $this->makeReimbursement($voucher, false);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert not visible to the sponsor
        $this->assertReimbursementNotInDashboard($reimbursement, $employee);

        // submit request
        $this->submitDraftReimbursement($reimbursement);

        // assert visible to the sponsor after submit
        $this->searchReimbursementInDashboard($reimbursement, $employee);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testStoreSubmittedReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($identity, $implementation->funds[0]);

        // make reimbursement and sponsor employee
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert the reimbursement is visible for the sponsor
        $this->searchReimbursementInDashboard($reimbursement, $employee);

        // assert that the employee cannot resolve the request
        $this->assertResolveReimbursementNotAllowed($reimbursement, $employee, true);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorAssignAndUnAssignReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeTestVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert that the employee cannot resolve the request
        $this->assertResolveReimbursementNotAllowed($reimbursement, $employee, true);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee, true);

        // assert that the reimbursement can be assigned by the sponsor
        $this->resignReimbursementInDashboard($reimbursement, $employee);

        // assert that the employee cannot resolve the request
        $this->assertResolveReimbursementNotAllowed($reimbursement, $employee, true);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorApproveReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeTestVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee, true);

        // assert that the reimbursement can be assigned by the sponsor
        $this->resolveReimbursementInDashboard($reimbursement, $employee, true, true);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorApproveReimbursementWithOffset(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeTestVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        $this->expireVoucherAndFund($reimbursement->voucher, now()->subDay())->refresh();

        $voucher->fund->fund_config->update([
            'reimbursement_approve_offset' => 0,
        ]);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee, false);

        // assert that the reimbursement can be assigned by the sponsor
        $this->resolveReimbursementInDashboard($reimbursement, $employee, true, false);

        $voucher->fund->fund_config->update([
            'reimbursement_approve_offset' => 1,
        ]);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee, true);

        // assert that the reimbursement can be assigned by the sponsor
        $this->resolveReimbursementInDashboard($reimbursement, $employee, true, true);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorRejectReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeTestVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee, true);

        // assert that the reimbursement can be assigned by the sponsor
        $this->resolveReimbursementInDashboard($reimbursement, $employee, false, true);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorReimbursementNotes(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeTestVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);
        $unassignedEmployee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee, true);

        // assert that the user can add notes to reimbursement
        $this->assertEmployeeCanAddNotesToReimbursement($reimbursement, $employee, $unassignedEmployee);
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Employee $employee
     * @return void
     */
    protected function searchReimbursementInDashboard(
        Reimbursement $reimbursement,
        Employee $employee,
    ): void {
        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $headers = $this->makeApiHeaders($employee->identity);

        $response = $this->getJson("$endpoint?" . http_build_query([
            'q' => $reimbursement->voucher->identity->email,
        ]), $headers);

        $response->assertSuccessful();
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Employee $employee
     * @return void
     */
    protected function assertReimbursementNotInDashboard(
        Reimbursement $reimbursement,
        Employee $employee,
    ): void {
        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $headers = $this->makeApiHeaders($employee->identity);

        $response = $this->getJson("$endpoint?" . http_build_query([
            'q' => $reimbursement->voucher->identity->email,
        ]), $headers);

        $response->assertJsonCount(0, 'data');
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Employee $employee
     * @param bool $approve
     * @return void
     */
    protected function assertResolveReimbursementNotAllowed(
        Reimbursement $reimbursement,
        Employee $employee,
        bool $approve
    ): void {
        $headers = $this->makeApiHeaders($employee->identity);

        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $endpoint = "$endpoint/$reimbursement->id/" . ($approve ? 'approve' : 'decline');

        $response = $this->postJson($endpoint, [], $headers);
        $response->assertForbidden();
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Employee $employee
     * @param Employee $unassignedEmployee
     * @return void
     */
    protected function assertEmployeeCanAddNotesToReimbursement(
        Reimbursement $reimbursement,
        Employee $employee,
        Employee $unassignedEmployee
    ): void {
        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $headers = $this->makeApiHeaders($employee->identity);
        $headersUnassigned = $this->makeApiHeaders($this->makeIdentityProxy($unassignedEmployee->identity));

        // assert unassigned employee can't add notes
        $description = $this->faker()->text(1000);
        $response = $this->postJson("$endpoint/$reimbursement->id/notes", compact('description'), $headersUnassigned);
        $response->assertForbidden();

        // assert assigned employee can add notes
        $description = $this->faker()->text(1000);
        $response = $this->postJson("$endpoint/$reimbursement->id/notes", compact('description'), $headers);
        $response->assertSuccessful();

        // find created not
        /** @var Note|null $note */
        $note = $reimbursement->refresh()->notes()->find($response->json('data.id'));
        $this->assertNotNull($note);

        // get all notes and check that the created not is in the list
        $response = $this->getJson("$endpoint/$reimbursement->id/notes", $headers);
        $response->assertSuccessful();
        $this->assertIsArray($response->json('data'));
        $this->assertNotNull(Arr::first($response->json('data'), fn ($item) => $item['id'] == $note->id));

        // assert employee can't remove others notes
        $response = $this->deleteJson("$endpoint/$reimbursement->id/notes/$note->id", [], $headersUnassigned);
        $response->assertForbidden();

        // assert assigned employee can remove their own notes
        $response = $this->deleteJson("$endpoint/$reimbursement->id/notes/$note->id", [], $headers);
        $response->assertSuccessful();
        $this->assertNull($reimbursement->notes()->find($note->id));
    }

    /**
     * @param Reimbursement $reimbursement
     * @return Reimbursement
     */
    protected function submitDraftReimbursement(Reimbursement $reimbursement): Reimbursement
    {
        $submitTime = now();
        $requesterEmail = $reimbursement->voucher->identity->email;
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($reimbursement->voucher->identity));

        $response = $this->patchJson("$this->apiUrl/$reimbursement->id", [
            'state' => $reimbursement::STATE_PENDING,
            'amount' => $reimbursement->amount,
            'voucher_id' => $reimbursement->voucher_id,
            'files' => $reimbursement->files->pluck('uid')->toArray(),
        ], $headers);

        $response->assertSuccessful();
        $response->assertJsonPath('data.state', $reimbursement::STATE_PENDING);

        $reimbursement->refresh();
        $this->assertTrue($reimbursement->isPending());
        $this->assertMailableSent($requesterEmail, ReimbursementSubmittedMail::class, $submitTime);

        return $reimbursement;
    }

    /**
     * @param Voucher $voucher
     * @throws Throwable
     * @return void
     */
    protected function makeInvalidReimbursement(Voucher $voucher): void
    {
        $proxy = $this->makeIdentityProxy($voucher->identity);
        $headers = $this->makeApiHeaders($proxy);

        $body = $this->makeReimbursementRequestBody($voucher, $headers);
        $bodyInvalid = array_fill_keys(array_keys($body), null);

        // assert has validation errors
        $response = $this->postJson($this->apiUrl, $bodyInvalid, $headers);
        $response->assertJsonValidationErrors(array_keys(array_except($body, 'description')));
    }
}
