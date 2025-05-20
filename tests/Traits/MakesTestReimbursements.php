<?php

namespace Tests\Traits;

use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Throwable;

trait MakesTestReimbursements
{
    use WithFaker;
    use DoesTesting;
    use AssertsSentEmails;

    /**
     * @var array
     */
    protected array $reimbursementResourceStructure = [
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
                ],
            ],
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
     * @param Voucher $voucher
     * @param bool $submit
     * @throws Throwable
     * @return Reimbursement
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
        $response = $this->postJson('/api/v1/platform/reimbursements', $body, $headers);
        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => $this->reimbursementResourceStructure,
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
     * @throws Throwable
     * @return string[]
     */
    protected function makeReimbursementRequestBody(?Voucher $voucher = null, array $headers = []): array
    {
        return [
            'title' => $this->faker->text(60),
            'description' => $this->faker->text(600),
            'amount' => random_int(1, 10),
            'iban' => $this->faker()->iban('NL'),
            'iban_name' => 'John Doe',
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
     * @param bool $assertSuccess
     * @return void
     */
    protected function assignReimbursementInDashboard(
        Reimbursement $reimbursement,
        Employee $employee,
        bool $assertSuccess,
    ): void {
        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $headers = $this->makeApiHeaders($employee->identity);

        $response = $this->postJson("$endpoint/$reimbursement->id/assign", [], $headers);

        if ($assertSuccess) {
            $response->assertSuccessful();
            $response->assertJsonPath('data.id', $reimbursement->id);
            $response->assertJsonPath('data.employee_id', $employee->id);
        } else {
            $response->assertForbidden();
        }
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
        $headers = $this->makeApiHeaders($employee->identity);

        $response = $this->postJson("$endpoint/$reimbursement->id/resign", [], $headers);
        $response->assertSuccessful();
        $response->assertJsonPath('data.id', $reimbursement->id);
        $response->assertJsonPath('data.employee_id', null);
    }

    /**
     * @param Reimbursement $reimbursement
     * @param Employee $employee
     * @param bool $approve
     * @param bool $assertSuccess
     * @return void
     */
    protected function resolveReimbursementInDashboard(
        Reimbursement $reimbursement,
        Employee $employee,
        bool $approve,
        bool $assertSuccess,
    ): void {
        $headers = $this->makeApiHeaders($employee->identity);
        $assertState = $approve ? $reimbursement::STATE_APPROVED : $reimbursement::STATE_DECLINED;

        $endpoint = "/api/v1/platform/organizations/$employee->organization_id/reimbursements";
        $endpoint = "$endpoint/$reimbursement->id/" . ($approve ? 'approve' : 'decline');

        $response = $this->postJson($endpoint, [], $headers);

        if ($assertSuccess) {
            $response->assertSuccessful();
            $response->assertJsonPath('data.id', $reimbursement->id);
            $response->assertJsonPath('data.state', $assertState);
        } else {
            $response->assertForbidden();
        }
    }
}
