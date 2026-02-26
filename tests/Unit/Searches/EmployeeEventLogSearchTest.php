<?php

namespace Tests\Unit\Searches;

use App\Models\Fund;
use App\Models\Permission;
use App\Models\Role;
use App\Searches\EmployeeEventLogSearch;
use App\Services\EventLogService\Models\EventLog;
use App\Traits\DoesTesting;
use Illuminate\Support\Facades\Config;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class EmployeeEventLogSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestBankConnections;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestVouchers;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->firstOrFail();
        $search = new EmployeeEventLogSearch($employee, [], EventLog::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQueryOnIdentityEmail(): void
    {
        [$organization, $employee] = $this->makeOrganizationEmployee();
        ['events' => $events, 'permissions' => $permissions] = $this->getEventConfig('fund');

        $fund = $this->makeTestFund($organization);

        $matchIdentity = $this->makeIdentity('match@test.com');
        $otherIdentity = $this->makeIdentity('other@test.com');
        $matchLogs = $this->createLogs($fund, $events, $matchIdentity->address);

        $this->createLogs($fund, $events, $otherIdentity->address);
        $role = $this->attachRoleToEmployee($employee);

        $this->assertSearchMatchesPermissions($role, $permissions, fn () => new EmployeeEventLogSearch($employee, [
            'q' => 'match@test.com',
            'loggable' => ['fund'],
        ], EventLog::query()), collect($matchLogs)->pluck('id')->toArray());
    }

    /**
     * @return void
     */
    public function testFiltersByLoggableTypeFund(): void
    {
        [$organization, $employee, $employeeIdentity] = $this->makeOrganizationEmployee();
        ['events' => $events, 'permissions' => $permissions] = $this->getEventConfig('fund');

        $fund = $this->makeTestFund($organization);
        $logs = $this->createLogs($fund, $events, $employeeIdentity->address);
        $role = $this->attachRoleToEmployee($employee);

        $this->assertSearchMatchesPermissions($role, $permissions, fn () => new EmployeeEventLogSearch($employee, [
            'loggable' => ['fund'],
        ], EventLog::query()), collect($logs)->pluck('id')->toArray());
    }

    /**
     * @return void
     */
    public function testFiltersByLoggableId(): void
    {
        [$organization, $employee, $employeeIdentity] = $this->makeOrganizationEmployee();
        ['events' => $events, 'permissions' => $permissions] = $this->getEventConfig('fund');

        $fund = $this->makeTestFund($organization);
        $otherFund = $this->makeTestFund($organization);
        $matchLogs = $this->createLogs($fund, $events, $employeeIdentity->address);

        $this->createLogs($otherFund, $events, $employeeIdentity->address);
        $role = $this->attachRoleToEmployee($employee);

        $this->assertSearchMatchesPermissions($role, $permissions, fn () => new EmployeeEventLogSearch($employee, [
            'loggable' => ['fund'],
            'loggable_id' => $fund->id,
        ], EventLog::query()), collect($matchLogs)->pluck('id')->toArray());
    }

    /**
     * @return void
     */
    public function testReturnsNoResultsWhenLoggableIsEmpty(): void
    {
        [$organization, $employee, $employeeIdentity] = $this->makeOrganizationEmployee();
        ['events' => $events, 'permissions' => $permissions] = $this->getEventConfig('fund');

        $fund = $this->makeTestFund($organization);
        $role = $this->attachRoleToEmployee($employee);

        $this->createLogs($fund, $events, $employeeIdentity->address);

        $this->assertSearchMatchesPermissions($role, $permissions, fn () => new EmployeeEventLogSearch($employee, [
            'loggable' => [],
        ], EventLog::query()), []);
    }

    /**
     * @return void
     */
    public function testVoucherExportedSpecialCase(): void
    {
        [$organization, $employee, $employeeIdentity] = $this->makeOrganizationEmployee();
        ['events' => $events, 'permissions' => $permissions] = $this->getEventConfig('voucher');

        $fund = $this->makeTestFund($organization);
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity(), dispatchCreated: false);

        $log = $fund->logs()->create([
            'event' => Fund::EVENT_VOUCHERS_EXPORTED,
            'data' => ['fund_export_voucher_ids' => [$voucher->id]],
            'identity_address' => $employeeIdentity->address,
        ]);

        $voucherLogs = $this->createLogs($voucher, $events, $employeeIdentity->address);
        $role = $this->attachRoleToEmployee($employee);

        $this->assertSearchMatchesPermissions($role, $permissions, fn () => new EmployeeEventLogSearch($employee, [
            'loggable' => ['voucher'],
            'loggable_id' => $voucher->id,
        ], EventLog::query()), collect($voucherLogs)->pluck('id')->push($log->id)->toArray());
    }

    /**
     * @return void
     */
    public function testFiltersByLoggableTypeBankConnection(): void
    {
        [$organization, $employee, $employeeIdentity] = $this->makeOrganizationEmployee();
        ['events' => $events, 'permissions' => $permissions] = $this->getEventConfig('bank_connection');

        $this->makeTestFund($organization);

        $bankConnection = $this->makeBankConnection($organization);
        $logs = $this->createLogs($bankConnection, $events, $employeeIdentity->address);
        $role = $this->attachRoleToEmployee($employee);

        $this->assertSearchMatchesPermissions($role, $permissions, fn () => new EmployeeEventLogSearch($employee, [
            'loggable' => ['bank_connection'],
        ], EventLog::query()), collect($logs)->pluck('id')->toArray());
    }

    /**
     * @return void
     */
    public function testFiltersByLoggableTypeEmployees(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $ownerEmployee = $organization->employees()->firstOrFail();
        $employeeIdentity = $this->makeIdentity('employee@test.com');
        $employee = $organization->employees()->create([
            'identity_address' => $employeeIdentity->address,
        ]);

        ['events' => $events, 'permissions' => $permissions] = $this->getEventConfig('employees');

        $logs = $this->createLogs($employee, $events, $employeeIdentity->address);
        $role = $this->attachRoleToEmployee($employee);
        $ownerLogs = $ownerEmployee->logs()->whereIn('event', $events)->pluck('id')->toArray();

        $this->assertSearchMatchesPermissions($role, $permissions, fn () => new EmployeeEventLogSearch($employee, [
            'loggable' => ['employees'],
        ], EventLog::query()), collect($logs)->pluck('id')->merge($ownerLogs)->toArray());
    }

    /**
     * @param Role $role
     * @param array $permissions
     * @param callable $buildSearch
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchMatchesPermissions(
        Role $role,
        array $permissions,
        callable $buildSearch,
        array $expectedIds,
    ): void {
        $expected = collect($expectedIds)->sort()->values()->toArray();

        foreach ($permissions as $permission) {
            $role->permissions()->sync(Permission::where('key', $permission)->pluck('id')->toArray());

            $search = $buildSearch();
            $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

            $this->assertSame($expected, $actual);
        }
    }

    /**
     * @return array
     */
    private function makeOrganizationEmployee(): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employeeIdentity = $this->makeIdentity('employee@test.com');
        $employee = $organization->addEmployee($employeeIdentity);

        return [$organization, $employee, $employeeIdentity];
    }

    /**
     * @param $employee
     * @return Role
     */
    private function attachRoleToEmployee($employee): Role
    {
        $role = Role::firstOrFail();
        $employee->roles()->sync([$role->id]);

        return $role;
    }

    /**
     * @param string $key
     * @return array
     */
    private function getEventConfig(string $key): array
    {
        $events = Config::get("forus.event_permissions.$key.events");
        $this->assertNotEmpty($events);

        $permissions = Config::get("forus.event_permissions.$key.permissions");
        $this->assertNotEmpty($permissions);

        return [
            'events' => $events,
            'permissions' => $permissions,
        ];
    }

    /**
     * @param $loggable
     * @param array $events
     * @param string $identityAddress
     * @return array
     */
    private function createLogs($loggable, array $events, string $identityAddress): array
    {
        return array_map(fn (string $event) => $loggable->logs()->create([
            'event' => $event,
            'data' => [],
            'identity_address' => $identityAddress,
        ]), $events);
    }
}
