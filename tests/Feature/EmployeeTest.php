<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentities;

class EmployeeTest extends TestCase
{
    use DatabaseTransactions, WithFaker, MakesTestIdentities;

    /**
     * @var string
     */
    protected string $apiMediaUrl = '/api/v1/platform/organizations/%s/employees';

    /**
     * @var array|string[]
     */
    protected array $resourceStructure = [
        'id',
        'email',
        'identity_address',
        'organization',
        'organization_id',
        'permissions',
        'roles'
    ];

    /**
     * @return void
     */
    public function testEmployeeStore(): void
    {
        $organization = Organization::first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));
        $employeeData = $this->makeEmployeeData();

        // make valid store employee request
        $this->storeEmployee($organization, $employeeData, $headers);

        // test missing roles
        $this->post($this->getListUrl($organization), [
            'email' => $employeeData['email'],
        ], $headers)->assertJsonValidationErrorFor('roles');

        // test missing email
        $this->post(sprintf($this->apiMediaUrl, $organization->id), [
            'roles' => $employeeData['roles'],
        ], $headers)->assertJsonValidationErrorFor('email');
    }

    /**
     * @return void
     */
    public function testEmployeeUpdate(): void
    {
        $organization = Organization::first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $employeeData = $this->makeEmployeeData();
        $employee = $this->storeEmployee($organization, $employeeData, $headers);

        /** @var Role $role */
        $roles = $this->getRandomRoleIds(2, $employeeData['roles']);
        $employeeData = array_merge($employeeData, compact('roles'));
        $response = $this->patch($this->getItemUrl($organization, $employee), $employeeData, $headers);
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $employee->unsetRelation('roles');
        $this->assertTrue($employee->roles->pluck('id')->diff($employeeData['roles'])->isEmpty());
    }

    /**
     * @return void
     */
    public function testEmployeeDelete(): void
    {
        $organization = Organization::where('name', 'Nijmegen')->first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $employeeData = $this->makeEmployeeData();
        $employee = $this->storeEmployee($organization, $employeeData, $headers);

        $response = $this->deleteJson($this->getItemUrl($organization, $employee), [], $headers);
        $response->assertSuccessful();

        $this->assertNull(Employee::find($employee->id));
    }

    /**
     * @return void
     */
    public function testEmployeeShow(): void
    {
        $organization = Organization::where('name', 'Nijmegen')->first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $this->storeEmployee($organization, $this->makeEmployeeData(), $headers);
    }
    /**
     * @return void
     */
    public function testEmployeeSearch(): void
    {
        $organization = Organization::where('name', 'Nijmegen')->first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $employeeData = $this->makeEmployeeData();
        $employee = $this->storeEmployee($organization, $employeeData, $headers);

        // make search by email query
        $q = $employee->identity->email;
        $params = http_build_query(compact('q'));

        // make search request
        $response = $this->getJson($this->getListUrl($organization) . "?$params", $headers);
        $response->assertSuccessful();
        $this->assertIsArray($response->json('data'));

        // only one employee should be returned and it should be the requested employee
        $this->assertTrue(count($response->json('data')) == 1);
        $this->assertTrue($response->json('data.0.id') == $employee->id);

        // search by permission
        $permission = 'manage_vouchers';
        $params = http_build_query(compact('permission'));

        // make search by permission query
        $response = $this->getJson($this->getListUrl($organization) . "?$params", $headers);
        $response->assertSuccessful();
        $this->assertIsArray($response->json('data'));

        $employee = Employee::find($response->json('data.0.id'));
        $this->assertNotNull($employee);
        $this->assertTrue($organization->employeesWithPermissions($permission)
            ->where('id', $employee->id)
            ->isNotEmpty());
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
        $response = $this->post($this->getListUrl($organization), $employeeData, $headers);
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $employee = Employee::find($response->json('data.id'));
        $this->assertNotNull($employee);
        $this->assertEquals($employee->identity->email, $employeeData['email']);
        $this->assertTrue($employee->roles->pluck('id')->diff($employeeData['roles'])->isEmpty());

        return $employee;
    }

    /**
     * @param int $count
     * @param array $notIn
     * @return array
     */
    protected function getRandomRoleIds(int $count = 1, array $notIn = []): array
    {
        return Role::inRandomOrder()
            ->whereNotIn('id', $notIn)
            ->limit($count)
            ->pluck('id')
            ->toArray();
    }

    /**
     * @return array
     */
    protected function makeEmployeeData(): array
    {
        return [
            'roles' => $this->getRandomRoleIds(2),
            'email' => $this->makeUniqueEmail(),
        ];
    }

    /**
     * @param Organization $organization
     * @return string
     */
    protected function getListUrl(Organization $organization): string
    {
        return sprintf($this->apiMediaUrl, $organization->id);
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @return string
     */
    protected function getItemUrl(Organization $organization, Employee $employee): string
    {
        return sprintf($this->apiMediaUrl, $organization->id) . '/'. $employee->id;
    }
}