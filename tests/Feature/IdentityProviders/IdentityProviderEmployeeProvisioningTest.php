<?php

namespace Tests\Feature\IdentityProviders;

use App\Mail\User\EmployeeAddedMail;
use App\Models\Employee;
use App\Models\FundRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Voucher;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class IdentityProviderEmployeeProvisioningTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestIdentityProviders;
    use MakesTestOrganizationOffices;
    use MakesTestOrganizations;
    use MakesTestVouchers;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('identity_providers.enabled', true);
    }

    /**
     * @return void
     */
    public function testCreateBuildsManagedEmployee(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext(IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE);

        foreach ([true, false] as $active) {
            $payload = $this->makeIdentityProviderScimUserPayload(['active' => $active]);
            $membership = $this->provisionIdentityProviderUser($connection, $token, $payload);
            $identity = $membership->identity;
            $employee = Employee::withTrashed()->findOrFail($membership->employee_id);

            $this->assertSame($identity->address, $employee->identity_address);
            $this->assertSame($connection->organization_id, $employee->organization_id);
            $this->assertSame($identity->id, $membership->external_identity->identity_id);
            $this->assertSame($payload['emails'][0]['value'], $identity->email);
            $this->assertTrue($identity->primary_email->verified);
            $this->assertSame($membership->id, $identity->identity_provider_managed_membership->id);
            $this->assertSame(!$active, $employee->trashed());
            $this->assertSame($active ? null : Employee::DELETION_REASON_PROVISIONING, $employee->deletion_reason);
            $this->assertFalse($employee->roles()->exists());
            $this->assertFalse($identity->proxies()->exists());
            $this->assertMailableNotSent($identity->email, EmployeeAddedMail::class);

            $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $membership->uid)
                ->assertOk()->assertJsonPath('active', $active);
        }
    }

    /**
     * @return void
     */
    public function testDisableAndReactivatePreservesEmployeeAndClearsAssignments(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext(IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE);
        $membership = $this->provisionIdentityProviderUser($connection, $token, $this->makeIdentityProviderScimUserPayload());
        $employee = $membership->employee;
        $role = Role::where('key', 'admin')->firstOrFail();
        $office = $this->makeOrganizationOffice($connection->organization);

        $employee->roles()->sync([$role->id]);
        $employee->update(['office_id' => $office->id]);

        $fund = $this->makeTestFund($connection->organization, ['default_validator_employee_id' => $employee->id]);

        $request = $fund->fund_requests()->create([
            'identity_id' => $this->makeIdentity()->id,
            'employee_id' => $employee->id,
            'state' => FundRequest::STATE_PENDING,
        ]);

        $proxy = $this->makeIdentityProviderProxy($membership);
        $voucher = $this->makeTestVoucher($fund, $membership->identity, amount: 100);
        $this->assertTrue($connection->organization->identityCan($membership->identity, Permission::MANAGE_EMPLOYEES));

        $patch = [
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [['op' => 'replace', 'path' => 'active', 'value' => false]],
        ];

        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)->assertOk();

        $this->assertTrue($employee->refresh()->trashed());
        $this->assertSame(Employee::DELETION_REASON_PROVISIONING, $employee->deletion_reason);
        $this->assertNull($request->refresh()->employee_id);
        $this->assertNull($fund->refresh()->default_validator_employee_id);
        $this->assertSame(Voucher::STATE_DEACTIVATED, $voucher->refresh()->state);
        $this->assertFalse($connection->organization->identityCan($membership->identity, Permission::MANAGE_EMPLOYEES));
        $this->apiGetIdentityRequest($proxy)->assertUnauthorized();

        $patch['Operations'][0]['value'] = true;
        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)->assertOk();

        $this->assertSame($employee->id, $membership->refresh()->employee_id);
        $this->assertFalse($employee->refresh()->trashed());
        $this->assertNull($employee->deletion_reason);
        $this->assertSame([$role->id], $employee->roles()->pluck('roles.id')->all());
        $this->assertSame($office->id, $employee->office_id);
        $this->assertNull($request->refresh()->employee_id);
        $this->assertNull($fund->refresh()->default_validator_employee_id);
        $this->assertSame(Voucher::STATE_ACTIVE, $voucher->refresh()->state);
        $this->apiGetIdentityRequest($proxy)->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testDeleteAndReprovisionRestoresTheSameEmployee(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext(IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE);
        $payload = $this->makeIdentityProviderScimUserPayload();
        $membership = $this->provisionIdentityProviderUser($connection, $token, $payload);
        $employee = $membership->employee;
        $role = Role::where('key', 'validation')->firstOrFail();
        $office = $this->makeOrganizationOffice($connection->organization);

        $employee->roles()->sync([$role->id]);
        $employee->update(['office_id' => $office->id]);

        $identity = $membership->identity;
        $proxy = $this->makeIdentityProviderProxy($membership);

        $this->apiIdentityProviderScimUsersRequest('DELETE', $connection, $token, uid: $membership->uid)->assertNoContent();

        $this->assertTrue($employee->refresh()->trashed());
        $this->assertSame($identity->id, $membership->refresh()->identity_id);
        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $membership->uid)->assertNotFound();
        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)->assertOk()->assertJsonPath('Resources', []);

        $restored = $this->provisionIdentityProviderUser($connection, $token, $payload);

        $this->assertSame($membership->id, $restored->id);
        $this->assertSame($identity->id, $restored->identity_id);
        $this->assertSame($employee->id, $restored->employee->id);
        $this->assertSame([$role->id], $restored->employee->roles()->pluck('roles.id')->all());
        $this->assertSame($office->id, $restored->employee->office_id);
        $this->apiGetIdentityRequest($proxy)->assertUnauthorized();

        $this->assertTrue($connection->logs()->where('event', IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_REPROVISIONED)->exists());
    }
}
