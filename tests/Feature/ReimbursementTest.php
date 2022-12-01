<?php

namespace Tests\Feature;

use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Note;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReimbursementTest extends TestCase
{
    use DatabaseTransactions, WithFaker, AssertsSentEmails;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/reimbursements';

    /**
     * @var array
     */
    protected array $resourceStructure = [
        'id',
        'title',
        'description',
        'amount',
        'amount_locale',
        'iban',
        'iban_name',
        'voucher_id',
        'code',
        'state',
        'state_locale',
        'lead_time_locale',
        'employee_id',
        'expired',
        'resolved',
        'identity_email',
        'identity_bsn',
        'employee',
        'fund' => [
            'id',
            'name',
            'organization_id',
            'logo',
            'organization' => [
                'id',
                'name',
                'logo',
            ],
        ],
        'files' => [
            '*' => [
                'identity_address',
                'original_name',
                'type',
                'ext',
                'uid',
                'order',
                'size',
                'url',
                'preview' => [
                    'original_name',
                    'type',
                    'ext',
                    'uid',
                    'dominant_color',
                    'sizes' => [
                        'thumbnail',
                    ],
                ]
            ]
        ],
        'resolved_at',
        'resolved_at_locale',
        'submitted_at',
        'submitted_at_locale',
        'expire_at',
        'expire_at_locale',
        'created_at',
        'created_at_locale',
    ];

    /**
     * @return void
     * @throws \Throwable
     */
    public function testStoreInvalidReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeInvalidReimbursement($this->makeVoucher($identity, $implementation->funds[0]));
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testStoreDraftAndSubmitReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeVoucher($identity, $implementation->funds[0]);

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
     * @return void
     * @throws \Throwable
     */
    public function testStoreSubmittedReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeVoucher($identity, $implementation->funds[0]);

        // make reimbursement and sponsor employee
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert the reimbursement is visible for the sponsor
        $this->searchReimbursementInDashboard($reimbursement, $employee);

        // assert that the employee cannot resolve the request
        $this->assertResolveReimbursementNotAllowed($reimbursement, $employee, true);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSponsorAssignAndUnAssignReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert that the employee cannot resolve the request
        $this->assertResolveReimbursementNotAllowed($reimbursement, $employee, true);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee);

        // assert that the reimbursement can be assigned by the sponsor
        $this->resignReimbursementInDashboard($reimbursement, $employee);

        // assert that the employee cannot resolve the request
        $this->assertResolveReimbursementNotAllowed($reimbursement, $employee, true);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSponsorApproveReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee);

        // assert that the reimbursement can be assigned by the sponsor
        $this->resolveReimbursementInDashboard($reimbursement, $employee, true);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSponsorRejectReimbursement(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee);

        // assert that the reimbursement can be assigned by the sponsor
        $this->resolveReimbursementInDashboard($reimbursement, $employee, false);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testSponsorReimbursementNotes(): void
    {
        $implementation = $this->findImplementation('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // make reimbursement and sponsor employee
        $voucher = $this->makeVoucher($identity, $implementation->funds[0]);
        $reimbursement = $this->makeReimbursement($voucher, true);
        $employee = $this->makeReimbursementValidatorEmployee($reimbursement);
        $unassignedEmployee = $this->makeReimbursementValidatorEmployee($reimbursement);

        // assert that the reimbursement can be assigned by the sponsor
        $this->assignReimbursementInDashboard($reimbursement, $employee);

        // assert that the user can add notes to reimbursement
        $this->assertEmployeeCanAddNotesToReimbursement($reimbursement, $employee, $unassignedEmployee);
    }

    /**
     * @param Reimbursement $reimbursement
     * @return Employee
     */
    protected function makeReimbursementValidatorEmployee(Reimbursement $reimbursement): Employee
    {
        return $reimbursement
            ->voucher
            ->fund
            ->organization
            ->addEmployee($this->makeIdentity(), Role::pluck('id')->toArray());
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
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));

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
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));

        $response = $this->getJson("$endpoint?" . http_build_query([
            'q' => $reimbursement->voucher->identity->email,
        ]), $headers);

        $response->assertJsonCount(0, 'data');
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Employee $employee
     * @return void
     */
    protected function assignReimbursementInDashboard(
        Reimbursement $reimbursement,
        Employee $employee
    ): void {
        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));

        $response = $this->postJson("$endpoint/$reimbursement->id/assign", [], $headers);
        $response->assertSuccessful();
        $response->assertJsonPath('data.id', $reimbursement->id);
        $response->assertJsonPath('data.employee_id', $employee->id);
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Employee $employee
     * @return void
     */
    protected function resignReimbursementInDashboard(
        Reimbursement $reimbursement,
        Employee $employee
    ): void {
        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));

        $response = $this->postJson("$endpoint/$reimbursement->id/resign", [], $headers);
        $response->assertSuccessful();
        $response->assertJsonPath('data.id', $reimbursement->id);
        $response->assertJsonPath('data.employee_id', null);
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Employee $employee
     * @param bool $approve
     * @return void
     */
    protected function resolveReimbursementInDashboard(
        Reimbursement $reimbursement,
        Employee $employee,
        bool $approve
    ): void {
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));
        $assertState = $approve ? $reimbursement::STATE_APPROVED : $reimbursement::STATE_DECLINED;

        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $endpoint = "$endpoint/$reimbursement->id/" . ($approve ? 'approve' : 'decline');

        $response = $this->postJson($endpoint, [], $headers);
        $response->assertSuccessful();
        $response->assertJsonPath('data.id', $reimbursement->id);
        $response->assertJsonPath('data.state', $assertState);
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
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));

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
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($employee->identity));
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
        $this->assertNotNull(Arr::first($response->json('data'), fn($item) => $item['id'] == $note->id));

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
     * @return void
     * @throws \Throwable
     */
    protected function makeInvalidReimbursement(Voucher $voucher): void
    {
        $proxy = $this->makeIdentityProxy($voucher->identity);
        $headers = $this->makeApiHeaders($proxy);

        $body = $this->makeReimbursementRequestBody($voucher, $headers);
        $bodyInvalid = array_fill_keys(array_keys($body), null);

        // assert has validation errors
        $response = $this->postJson($this->apiUrl, $bodyInvalid, $headers);
        $response->assertJsonValidationErrors(array_keys($body));
    }

    /**
     * @param Voucher $voucher
     * @param bool $submit
     * @return Reimbursement
     * @throws \Throwable
     */
    protected function makeReimbursement(Voucher $voucher, bool $submit): Reimbursement
    {
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($voucher->identity));
        $submitTime = now();
        $requesterEmail = $voucher->identity->email;

        $body = array_merge($this->makeReimbursementRequestBody($voucher, $headers), [
           'state' => $submit ? 'pending' : 'draft',
        ]);

        // assert created
        $response = $this->postJson($this->apiUrl, $body, $headers);
        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => $this->resourceStructure,
        ]);

        $reimbursementId = $response->json('data.id');
        $reimbursement = $voucher->refresh()->reimbursements->where('id', $reimbursementId)[0] ?? null;

        $this->assertNotNull($reimbursement);
        $this->assertTrue($submit ? $reimbursement->isPending() : $reimbursement->isDraft());

        if ($submit) {
            $this->assertMailableSent($requesterEmail, ReimbursementSubmittedMail::class, $submitTime);
        }

        return $reimbursement;
    }

    /**
     * @return string[]
     * @throws \Throwable
     */
    protected function makeReimbursementRequestBody(?Voucher $voucher = null, array $headers = []): array
    {
        return [
            'title' => fake()->text(60),
            'description' => fake()->text(600),
            'amount' => random_int(1, 10),
            'iban' => $this->faker()->iban('NL'),
            'iban_name' => $this->faker()->firstName . ' ' . $this->faker()->lastName,
            'voucher_id' => $voucher?->id,
            'files' => [
                $this->makeReimbursementProofFile($headers)->json('data.uid'),
            ],
        ];
    }

    /**
     * @param array $headers
     * @return \Illuminate\Testing\TestResponse
     */
    protected function makeReimbursementProofFile(array $headers): TestResponse
    {
        $type = 'reimbursement_proof';
        $filePath = base_path('tests/assets/test.png');
        $file = UploadedFile::fake()->createWithContent($this->faker()->uuid . '.png', $filePath);

        $response = $this->postJson('/api/v1/files', compact('type', 'file'), $headers);
        $response->assertCreated();

        return $response;
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @return Voucher
     */
    protected function makeVoucher(Identity $identity, Fund $fund): Voucher
    {
        return $fund->makeVoucher($identity->address);
    }
}
