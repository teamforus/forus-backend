<?php

namespace Tests\Unit\Searches;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Searches\EmployeesSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestOrganizations;

class EmployeesSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new EmployeesSearch([], Employee::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByRole(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $role1 = $this->createRole('test_role_1');
        $role2 = $this->createRole('test_role_2');

        $employee1 = $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role1->id]);
        $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role2->id]);

        $this->assertSearchIds(['role' => $role1->key], [$employee1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByRoles(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $role1 = $this->createRole('test_role_1');
        $role2 = $this->createRole('test_role_2');
        $role3 = $this->createRole('test_role_3');

        $employee1 = $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role1->id]);
        $employee2 = $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role2->id]);
        $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role3->id]);

        $this->assertSearchIds([
            'roles' => [$role1->key, $role2->key],
        ], [$employee1->id, $employee2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByPermission(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $permission1 = $this->createPermission('test_permission_1');
        $permission2 = $this->createPermission('test_permission_2');
        $role1 = $this->createRole('test_role_1', [$permission1->key]);
        $role2 = $this->createRole('test_role_2', [$permission2->key]);

        $employee1 = $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role1->id]);
        $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role2->id]);

        $this->assertSearchIds(['permission' => $permission1->key], [$employee1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByPermissions(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $permission1 = $this->createPermission('test_permission_1');
        $permission2 = $this->createPermission('test_permission_2');
        $permission3 = $this->createPermission('test_permission_3');
        $role1 = $this->createRole('test_role_1', [$permission1->key]);
        $role2 = $this->createRole('test_role_2', [$permission2->key]);
        $role3 = $this->createRole('test_role_3', [$permission3->key]);

        $employee1 = $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role1->id]);
        $employee2 = $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role2->id]);
        $this->createEmployeeWithRoles($organization, $this->makeIdentity(), [$role3->id]);

        $this->assertSearchIds([
            'permissions' => [$permission1->key, $permission2->key],
        ], [$employee1->id, $employee2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $matchIdentity = $this->makeIdentity('match@test.com');
        $otherIdentity = $this->makeIdentity('other@test.com');

        $matchEmployee = $this->createEmployeeWithRoles($organization, $matchIdentity, []);
        $this->createEmployeeWithRoles($organization, $otherIdentity, []);

        $this->assertSearchIds(['q' => 'match@'], [$matchEmployee->id]);
    }

    /**
     * @param string $key
     * @param array $permissionKeys
     * @return Role
     */
    private function createRole(string $key, array $permissionKeys = []): Role
    {
        $role = Role::firstOrCreate(['key' => $key]);

        if ($permissionKeys) {
            $role->permissions()->sync(Permission::whereIn('key', $permissionKeys)->pluck('id')->toArray());
        }

        return $role;
    }

    /**
     * @param string $key
     * @return Permission
     */
    private function createPermission(string $key): Permission
    {
        $name = str_replace('_', ' ', $key);

        return Permission::firstOrCreate(['key' => $key], ['name' => $name]);
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param array $roleIds
     * @return Employee
     */
    private function createEmployeeWithRoles(
        Organization $organization,
        Identity $identity,
        array $roleIds,
    ): Employee {
        return $organization->addEmployee($identity, $roleIds);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = new EmployeesSearch($filters, Employee::query());
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
