<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;

class EmployeeTest extends TestCase
{
    use WithFaker;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var array|string[]
     */
    protected array $employeeResourceStructure = [
        'id',
        'email',
        'identity_address',
        'organization',
        'organization_id',
        'permissions',
        'roles',
    ];

    /**
     * @return void
     */
    public function testEmployeeStore(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $headers = $this->makeApiHeaders($identity);
        $employeeData = $this->makeEmployeeData(['admin', 'finance']);

        // make valid store employee request
        $employee = $this->storeEmployee($organization, $employeeData, $headers);
        $this->checkEmployeePermissions($organization, $employee, Role::byKey('admin')->permissions);
        $this->checkEmployeePermissions($organization, $employee, Role::byKey('finance')->permissions);

        // test missing roles
        $this->postJson("/api/v1/platform/organizations/$organization->id/employees", [
            'email' => $employeeData['email'],
        ], $headers)->assertJsonValidationErrorFor('roles');

        // test missing email
        $this->postJson("/api/v1/platform/organizations/$organization->id/employees", [
            'roles' => $employeeData['roles'],
        ], $headers)->assertJsonValidationErrorFor('email');
    }

    /**
     * @return void
     */
    public function testEmployeeUpdate(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $headers = $this->makeApiHeaders($identity);

        $employeeData = $this->makeEmployeeData(['admin', 'finance']);
        $employee = $this->storeEmployee($organization, $employeeData, $headers);

        $roles = Role::whereIn('key', ['validation', 'supervisor_validator'])->pluck('id')->toArray();
        $employeeData = array_merge($employeeData, compact('roles'));

        $response = $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/employees/$employee->id",
            $employeeData,
            $headers
        );

        $response->assertJsonStructure(['data' => $this->employeeResourceStructure]);

        $employee->unsetRelation('roles');
        $this->assertTrue($employee->roles->pluck('id')->diff($employeeData['roles'])->isEmpty());

        $newPermissions = Role::whereIn('key', ['validation', 'supervisor_validator'])
            ->get()
            ->pluck('permissions')
            ->collapse()
            ->unique('id');

        $this->checkEmployeePermissions($organization, $employee, $newPermissions);

        $newPermissionsIds = $newPermissions->pluck('id')->toArray();
        $prevPermissions = Role::whereIn('key', ['admin', 'finance'])
            ->get()
            ->pluck('permissions')
            ->collapse()
            ->unique('id')
            ->filter(fn (Permission $p) => !in_array($p->id, $newPermissionsIds));

        $this->checkEmployeePermissions($organization, $employee, $prevPermissions, false);
    }

    /**
     * @return void
     */
    public function testEmployeeDelete(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $owner = $organization->findEmployee($identity);

        $employeeData = $this->makeEmployeeData(['admin', 'finance']);
        $employee = $this->storeEmployee($organization, $employeeData, $this->makeApiHeaders($identity));

        $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/employees/$employee->id",
            [],
            $this->makeApiHeaders($employee->identity)
        )->assertForbidden();

        $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/employees/$owner->id",
            [],
            $this->makeApiHeaders($employee->identity)
        )->assertForbidden();

        $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/employees/$employee->id",
            [],
            $this->makeApiHeaders($identity)
        )->assertSuccessful();

        $this->assertNull(Employee::find($employee->id));
    }

    /**
     * @return void
     */
    public function testEmployeeShow(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $headers = $this->makeApiHeaders($identity);

        $employee = $this->storeEmployee($organization, $this->makeEmployeeData(['admin', 'finance']), $headers);

        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/employees/$employee->id",
            $headers
        );

        $response->assertJsonStructure(['data' => $this->employeeResourceStructure]);
    }

    /**
     * @return void
     */
    public function testEmployeeSearch(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $headers = $this->makeApiHeaders($identity);

        $employeeData = $this->makeEmployeeData(['admin', 'finance']);
        $employee = $this->storeEmployee($organization, $employeeData, $headers);

        // make search by email query
        $q = $employee->identity->email;
        $params = http_build_query(compact('q'));

        // make search request
        $response = $this->getJson("/api/v1/platform/organizations/$organization->id/employees?$params", $headers);
        $response->assertSuccessful();
        $this->assertIsArray($response->json('data'));

        // only one employee should be returned and it should be the requested employee
        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.id') == $employee->id);

        // search by permission
        $permission = 'manage_vouchers';
        $params = http_build_query(compact('permission'));

        // make search by permission query
        $response = $this->getJson("/api/v1/platform/organizations/$organization->id/employees?$params", $headers);
        $response->assertSuccessful();
        $this->assertIsArray($response->json('data'));
        $data = collect($response->json('data'));

        // only two employees should be returned (base when created organization and the second which was created)
        $this->assertCount(2, $data);

        $validEmployeeIds = [
            $organization->findEmployee($identity)->id,
            $employee->id,
        ];

        $this->assertTrue($data->pluck('id')->diff($validEmployeeIds)->isEmpty());

        $data->each(function ($item) use ($organization, $permission) {
            $employee = Employee::find($item['id']);
            $this->assertNotNull($employee);

            $this->assertTrue(
                $organization->employeesWithPermissions($permission)
                    ->where('id', $employee->id)
                    ->isNotEmpty()
            );
        });
    }

    /**
     * @return void
     */
    public function testEmployeeTransferOwnership(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $headers = $this->makeApiHeaders($identity);

        $this->assertTrue($organization->isOwner($identity));

        // make employee with a finance role
        $employeeData = $this->makeEmployeeData(['finance']);
        $employee = $this->storeEmployee($organization, $employeeData, $headers);

        // assert validation error when employee doesnt have admin role
        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/transfer-ownership",
            ['employee_id' => $employee->id],
            $headers
        )->assertJsonValidationErrors(['employee_id']);

        // add an admin role to employee and assert transfer success
        $roles = Role::whereIn('key', ['admin'])->pluck('id')->toArray();
        $employeeData = array_merge($employeeData, compact('roles'));

        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/employees/$employee->id",
            $employeeData,
            $headers
        )->assertSuccessful();

        $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/transfer-ownership",
            ['employee_id' => $employee->id],
            $headers
        )->assertSuccessful();

        $organization->refresh();
        $this->assertFalse($organization->isOwner($identity));
        $this->assertTrue($organization->isOwner($employee->identity));
    }

    /**
     * @param Organization $organization
     * @param array $employeeData
     * @param array $headers
     * @return Employee
     */
    protected function storeEmployee(
        Organization $organization,
        array $employeeData,
        array $headers = []
    ): Employee {
        $response = $this->postJson(
            "/api/v1/platform/organizations/$organization->id/employees",
            $employeeData,
            $headers
        );

        $response->assertJsonStructure(['data' => $this->employeeResourceStructure]);

        $employee = Employee::find($response->json('data.id'));
        $this->assertNotNull($employee);
        $this->assertEquals($employee->identity->email, $employeeData['email']);
        $this->assertTrue($employee->roles->pluck('id')->diff($employeeData['roles'])->isEmpty());

        return $employee;
    }

    /**
     * @param array $roles
     * @return array
     */
    protected function makeEmployeeData(array $roles): array
    {
        return [
            'roles' => Role::whereIn('key', $roles)->pluck('id')->toArray(),
            'email' => $this->makeUniqueEmail(),
        ];
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param Collection $permissions
     * @param bool $assert
     * @return void
     */
    private function checkEmployeePermissions(
        Organization $organization,
        Employee $employee,
        Collection $permissions,
        bool $assert = true,
    ): void {
        foreach ($permissions as $permission) {
            $assert
                ? $this->assertTrue($organization->identityCan($employee->identity, $permission->key))
                : $this->assertFalse($organization->identityCan($employee->identity, $permission->key));
        }
    }
}
