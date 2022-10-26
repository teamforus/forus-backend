<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\Role;
use App\Scopes\Builders\EmployeeQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * @var string
     */
    protected string $apiMediaUrl = '/api/v1/platform/organizations/%s/employees';

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
        $organization = Organization::where('name', 'Nijmegen')->first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $role  = Role::first();
        $email = 'test'. time() .'@example.com';

        $response = $this->post(sprintf($this->apiMediaUrl, $organization->id), [
            'email' => $email,
            'roles' => [$role->id]
        ], $headers);
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $employee = Employee::find($response->json('data.id'));

        $this->assertNotNull($employee);
        $this->assertEquals($email, $employee->identity->email);
        $this->assertContains($role->id, $employee->roles->pluck('id'));

        $this->post(sprintf($this->apiMediaUrl, $organization->id), [
            'email' => $email,
        ], $headers)->assertJsonValidationErrorFor('roles');
    }

    /**
     * @return void
     */
    public function testEmployeeUpdate(): void
    {
        $organization = Organization::where('name', 'Nijmegen')->first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->post(sprintf($this->apiMediaUrl, $organization->id), [
            'email' => 'test'. time() .'@example.com',
            'roles' => [Role::first()->id]
        ], $headers);
        $employee = Employee::find($response->json('data.id'));
        $this->assertNotNull($employee);

        /** @var Role $role */
        $role = Role::inRandomOrder()->first();
        $response = $this->patch(sprintf($this->apiMediaUrl, $organization->id). '/'. $employee->id, [
            'roles' => [$role->id]
        ], $headers);
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $employee->refresh();
        $this->assertContains($role->id, $employee->roles->pluck('id'));
    }

    /**
     * @return void
     */
    public function testEmployeeDelete(): void
    {
        $organization = Organization::where('name', 'Nijmegen')->first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->post(sprintf($this->apiMediaUrl, $organization->id), [
            'email' => 'test'. time() .'@example.com',
            'roles' => [Role::first()->id]
        ], $headers);
        $employee = Employee::find($response->json('data.id'));
        $this->assertNotNull($employee);

        $response = $this->delete(sprintf($this->apiMediaUrl, $organization->id). '/'. $employee->id, [], $headers);
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

        $email = 'test'. time() .'@example.com';
        $role = Role::first();
        $response = $this->post(sprintf($this->apiMediaUrl, $organization->id), [
            'email' => $email,
            'roles' => [$role->id]
        ], $headers);
        $employee = Employee::find($response->json('data.id'));
        $this->assertNotNull($employee);

        $response = $this->get(sprintf($this->apiMediaUrl, $organization->id). '/'. $employee->id, $headers);
        $response->assertSuccessful();
        $this->assertIsArray($response->json('data'));

        $this->assertNotNull($employee);
        $this->assertEquals($employee->identity->email, $email);
        $this->assertContains($role->id, $employee->roles->pluck('id'));
    }
    /**
     * @return void
     */
    public function testEmployeeSearch(): void
    {
        $organization = Organization::where('name', 'Nijmegen')->first();
        $this->assertNotNull($organization);
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // search by email
        $params = http_build_query([
            'q' => $organization->identity->email,
        ]);
        $response = $this->get(sprintf($this->apiMediaUrl, $organization->id).'?'.$params, $headers);
        $response->assertSuccessful();
        $this->assertIsArray($response->json('data'));

        $employee = Employee::find($response->json('data')[0]['id']);
        $this->assertNotNull($employee);
        $this->assertEquals($employee->identity->email, $organization->identity->email);

        // search by permission
        $permissionFilter = 'manage_vouchers';
        $params = http_build_query([
            'permission' => $permissionFilter,
        ]);
        $response = $this->get(sprintf($this->apiMediaUrl, $organization->id).'?'.$params, $headers);
        $response->assertSuccessful();
        $this->assertIsArray($response->json('data'));

        $employee = Employee::find($response->json('data')[0]['id']);
        $this->assertNotNull($employee);
        $this->assertContains($employee->id, EmployeeQuery::whereHasPermissionFilter(
            $organization->employees(), $permissionFilter)->pluck('id')
        );
    }
}