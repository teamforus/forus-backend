<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
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
     * Tests the storage of an employee within an organization.
     *
     * @return void
     */
    public function testEmployeeStore(): void
    {
        $identity = $this->makeIdentity();
        $identityHeaders = $this->makeApiHeaders($identity);

        $organization = $this->makeTestOrganization($identity);
        $employeeData = $this->makeEmployeeData(roles: ['admin', 'finance']);

        // Assert employee store errors
        $this->storeEmployee($organization, ['email' => $employeeData['email']], $identityHeaders, ['roles']);
        $this->storeEmployee($organization, ['roles' => $employeeData['roles']], $identityHeaders, ['email']);
        $this->storeEmployee($organization, ['roles' => '###'], $identityHeaders, ['email']);

        // Assert employee store success
        $employee = $this->storeEmployee($organization, $employeeData, $identityHeaders);
        $this->assertEmployeeNotEmptyAndMatchesData($employee, $employeeData);
    }

    /**
     * Tests the updating of an employee's data within an organization.
     *
     * @return void
     */
    public function testEmployeeUpdate(): void
    {
        $identity = $this->makeIdentity();
        $identityHeaders = $this->makeApiHeaders($identity);

        $organization = $this->makeTestOrganization($identity);
        $employeeData = $this->makeEmployeeData(roles: ['admin', 'finance']);

        // create employee
        $employee = $this->storeEmployee($organization, $employeeData, $identityHeaders);
        $this->assertEmployeeNotEmptyAndMatchesData($employee, $employeeData);

        $employeeDataUpdate = $this->makeEmployeeData(
            roles: ['validation', 'supervisor_validator'],
            email: $employeeData['email'],
        );

        // Assert employee update errors
        $this->updateEmployee($organization, $employee, ['roles' => [1000]], $identityHeaders, ['roles.0']);

        // Assert employee update success
        $employee = $this->updateEmployee($organization, $employee, $employeeDataUpdate, $identityHeaders);
        $this->assertEmployeeNotEmptyAndMatchesData($employee, $employeeDataUpdate);
    }

    /**
     * Tests the deletion of an employee within an organization.
     *
     * @return void
     */
    public function testEmployeeDelete(): void
    {
        $identity = $this->makeIdentity();
        $identityHeaders = $this->makeApiHeaders($identity);

        $organization = $this->makeTestOrganization($identity);
        $owner = $organization->findEmployee($identity);

        $employeeData = $this->makeEmployeeData(roles: ['admin', 'finance']);
        $employee = $this->storeEmployee($organization, $employeeData, $identityHeaders);

        // An employee cannot delete themselves.
        $this->deleteEmployee($organization, $employee, $this->makeApiHeaders($employee->identity))->assertForbidden();

        // The owner of the organization cannot be deleted by another employee.
        $this->deleteEmployee($organization, $owner, $this->makeApiHeaders($employee->identity))->assertForbidden();

        // A non-owner employee can be successfully deleted.
        $this->deleteEmployee($organization, $employee, $identityHeaders)->assertSuccessful();

        $this->assertNull(Employee::find($employee->id));
    }

    /**
     * @return void
     */
    public function testEmployeeShow(): void
    {
        $identity = $this->makeIdentity();
        $identityHeaders = $this->makeApiHeaders($identity);

        $organization = $this->makeTestOrganization($identity);
        $employee = $this->storeEmployee($organization, $this->makeEmployeeData(['admin', 'finance']), $identityHeaders);

        $this->getEmployee($organization, $employee, $identityHeaders);
    }

    /**
     * Tests the functionality of searching for employees within an organization.
     *
     * @return void
     */
    public function testEmployeeSearch(): void
    {
        $identity = $this->makeIdentity();
        $identityHeaders = $this->makeApiHeaders($identity);

        $organization = $this->makeTestOrganization($identity);
        $employeeData = $this->makeEmployeeData(['admin', 'finance']);
        $employeeData2 = $this->makeEmployeeData(['operation_officer']);

        $employee = $this->storeEmployee($organization, $employeeData, $identityHeaders);
        $employee2 = $this->storeEmployee($organization, $employeeData2, $identityHeaders);

        $this->assertEmployeeNotEmptyAndMatchesData($employee, $employeeData);
        $this->assertEmployeeNotEmptyAndMatchesData($employee2, $employeeData2);

        // Search employee by email and assert only requested employee was found
        $this
            ->searchEmployee($organization, ['q' => $employee->identity->email], $identityHeaders)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $employee->id);

        // Search employee by permissions and assert that only matching employees found
        $this
            ->searchEmployee($organization, ['permission' => 'manage_vouchers', 'order_by' => 'created_at'], $identityHeaders)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $organization->findEmployee($identity)->id)
            ->assertJsonPath('data.1.id', $employee->id);
    }

    /**
     * Tests the transfer of ownership from an organization to an employee.
     */
    public function testEmployeeTransferOwnership(): void
    {
        $identity = $this->makeIdentity();
        $identityHeaders = $this->makeApiHeaders($identity);
        $organization = $this->makeTestOrganization($identity);

        $this->assertTrue($organization->isOwner($identity));

        // make employee with non admin role
        $employeeData = $this->makeEmployeeData(['finance']);
        $employee = $this->storeEmployee($organization, $employeeData, $identityHeaders);

        $this->assertEmployeeNotEmptyAndMatchesData($employee, $employeeData);

        // An employee with a non-admin role cannot be transferred ownership
        $this->transferOwnership($organization, $employee, $identityHeaders)->assertJsonValidationErrors(['employee_id']);

        // After granting admin privileges to an employee, they can be successfully transferred ownership.
        $this->updateEmployee($organization, $employee, $this->makeEmployeeData(roles: ['admin']), $identityHeaders);
        $this->transferOwnership($organization, $employee, $identityHeaders)->assertSuccessful();

        // The original owner's ownership status is revoked upon successful transfer.
        $organization->refresh();
        $this->assertFalse($organization->isOwner($identity));
        $this->assertTrue($organization->isOwner($employee->identity));
    }

    /**
     * @param array $roles
     * @return Collection
     */
    protected function findRolePermissions(array $roles): Collection
    {
        return Permission::query()->whereHas('roles', fn ($b) => $b->whereIn('key', $roles))->get();
    }

    /**
     * @param Organization $organization
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function searchEmployee(
        Organization $organization,
        array $params,
        array $headers = [],
    ): TestResponse {
        $params = http_build_query($params);
        $response = $this->getJson("/api/v1/platform/organizations/$organization->id/employees?$params", $headers);

        $response->assertSuccessful();
        $response->assertJsonIsArray('data');

        return $response;
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param array $headers
     * @return Employee|null
     */
    protected function getEmployee(
        Organization $organization,
        Employee $employee,
        array $headers = [],
    ): ?Employee {
        $response = $this->getJson("/api/v1/platform/organizations/$organization->id/employees/$employee->id", $headers);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => $this->employeeResourceStructure]);

        return Employee::query()->find($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param array $data
     * @param array $headers
     * @param array $assertErrors
     * @return Employee|null
     */
    protected function storeEmployee(
        Organization $organization,
        array $data,
        array $headers = [],
        array $assertErrors = [],
    ): ?Employee {
        return $this->assertEmployeeResponse(
            $this->postJson("/api/v1/platform/organizations/$organization->id/employees", $data, $headers),
            $assertErrors,
        );
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param array $data
     * @param array $headers
     * @param array $assertErrors
     * @return Employee|null
     */
    protected function updateEmployee(
        Organization $organization,
        Employee $employee,
        array $data,
        array $headers = [],
        array $assertErrors = [],
    ): ?Employee {
        return $this->assertEmployeeResponse(
            $this->patchJson("/api/v1/platform/organizations/$organization->id/employees/$employee->id", $data, $headers),
            $assertErrors,
        );
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param array $headers
     * @return Employee|null
     */
    protected function deleteEmployee(
        Organization $organization,
        Employee $employee,
        array $headers = [],
    ): ?TestResponse {
        return $this->deleteJson(
            "/api/v1/platform/organizations/$organization->id/employees/$employee->id",
            [],
            $headers,
        );
    }

    /**
     * @param TestResponse $response
     * @param array $assertErrors
     * @return Employee|null
     */
    protected function assertEmployeeResponse(
        TestResponse $response,
        array $assertErrors = [],
    ): ?Employee {
        if ($assertErrors) {
            foreach ($assertErrors as $error) {
                $response->assertJsonValidationErrorFor($error);
            }

            return null;
        }

        $response->assertJsonStructure(['data' => $this->employeeResourceStructure]);

        return Employee::query()->find($response->json('data.id'));
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param array $headers
     * @return TestResponse
     */
    protected function transferOwnership(
        Organization $organization,
        Employee $employee,
        array $headers = []
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/transfer-ownership",
            ['employee_id' => $employee->id],
            $headers,
        );
    }

    /**
     * @param Employee $employee
     * @param array $employeeData
     * @return void
     */
    protected function assertEmployeeNotEmptyAndMatchesData(Employee $employee, array $employeeData): void
    {
        $this->assertNotNull($employee);

        $this->assertSame($employeeData['email'], $employee->identity->email);
        $this->assertSame($employeeData['roles'], $employee->roles->pluck('id')->toArray());

        foreach ($employeeData['roles'] as $role) {
            $this->checkEmployeePermissions($employee->organization, $employee, Role::query()->find($role)->permissions);
        }
    }

    /**
     * @param array $roles
     * @param string|null $email
     * @return array
     */
    protected function makeEmployeeData(array $roles, string $email = null): array
    {
        return [
            'email' => $email ?: ($email === null ? $this->makeUniqueEmail() : false),
            'roles' => Role::whereIn('key', $roles)->pluck('id')->toArray(),
        ];
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param Collection|Permission[] $permissions
     * @param bool $assert
     * @return void
     */
    private function checkEmployeePermissions(
        Organization $organization,
        Employee $employee,
        Collection|Arrayable $permissions,
        bool $assert = true,
    ): void {
        foreach ($permissions as $permission) {
            $assert
                ? $this->assertTrue($organization->identityCan($employee->identity, $permission->key))
                : $this->assertFalse($organization->identityCan($employee->identity, $permission->key));
        }
    }
}
