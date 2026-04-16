<?php

namespace Tests\Unit\Searches;

use App\Models\Employee;
use App\Models\FundRequest;
use App\Searches\FundRequestSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new FundRequestSearch([], FundRequest::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $emailPart1 = 'unique';
        $emailPart2 = 'other';

        $fundNamePart1 = 'first';
        $fundNamePart2 = 'second';

        $bsnPart1 = '11111';
        $bsnPart2 = '22222';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization, ['name' => "$fundNamePart1 fund name"]);
        $fund2 = $this->makeTestFund($organization, ['name' => "$fundNamePart2 fund name"]);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail($emailPart1), bsn: "{$bsnPart1}8888");
        $identity2 = $this->makeIdentity($this->makeUniqueEmail($emailPart2), bsn: "{$bsnPart2}8888");

        $fundRequest1 = $this->makeFundRequestForIdentity($fund1, $identity1);
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity2);

        // assert filter by email, bsn, request ID
        $this->assertSearchIds(['q' => $emailPart1], [$fundRequest1->id], $employee);
        $this->assertSearchIds(['q' => $bsnPart1], [$fundRequest1->id], $employee);
        $this->assertSearchIds(['q' => $fundNamePart1], [$fundRequest1->id], $employee);
        $this->assertSearchIds(['q' => $fundRequest1->id], [$fundRequest1->id], $employee);

        $this->assertSearchIds(['q' => $emailPart2], [$fundRequest2->id], $employee);
        $this->assertSearchIds(['q' => $bsnPart2], [$fundRequest2->id], $employee);
        $this->assertSearchIds(['q' => $fundNamePart2], [$fundRequest2->id], $employee);
        $this->assertSearchIds(['q' => $fundRequest2->id], [$fundRequest2->id], $employee);
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequest1 = $this->makeFundRequestForIdentity($fund1, $identity);
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$fundRequest1->id], $employee);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$fundRequest2->id], $employee);
    }

    /**
     * @return void
     */
    public function testFiltersByState(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequest1 = $this->makeFundRequestForIdentity($fund1, $identity);
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity);

        $this->assertSearchIds(['state' => FundRequest::STATE_PENDING], [$fundRequest1->id, $fundRequest2->id], $employee);
        $this->assertSearchIds(['state' => FundRequest::STATE_APPROVED], [], $employee);

        $fundRequest1->assignEmployee($employee)->approve();

        $this->assertSearchIds(['state' => FundRequest::STATE_PENDING], [$fundRequest2->id], $employee);
        $this->assertSearchIds(['state' => FundRequest::STATE_APPROVED], [$fundRequest1->id], $employee);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByArchived(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequest1 = $this->makeFundRequestForIdentity($fund1, $identity);
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity);

        $this->assertSearchIds(['archived' => false], [$fundRequest1->id, $fundRequest2->id], $employee);
        $this->assertSearchIds(['archived' => true], [], $employee);

        $fundRequest1->assignEmployee($employee)->decline();

        $this->assertSearchIds(['archived' => false], [$fundRequest2->id], $employee);
        $this->assertSearchIds(['archived' => true], [$fundRequest1->id], $employee);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByEmployeeId(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee1 = $organization->addEmployee($this->makeIdentity());
        $employee2 = $organization->addEmployee($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequest1 = $this->makeFundRequestForIdentity($fund1, $identity)->assignEmployee($employee1);
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity)->assignEmployee($employee2);

        $this->assertSearchIds(['employee_id' => $employee1->id], [$fundRequest1->id]);
        $this->assertSearchIds(['employee_id' => $employee2->id], [$fundRequest2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByAssigned(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequest = $this->makeFundRequestForIdentity($fund1, $identity)->assignEmployee($employee);
        $this->makeFundRequestForIdentity($fund2, $identity);

        $this->assertSearchIds(['assigned' => true], [$fundRequest->id], $employee);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByIdentityId(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $identityOther = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequest1 = $this->makeFundRequestForIdentity($fund1, $identity);
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity);
        $fundRequestOther = $this->makeFundRequestForIdentity($fund2, $identityOther);

        $this->assertSearchIds(['identity_id' => $identity->id], [$fundRequest1->id, $fundRequest2->id]);
        $this->assertSearchIds(['identity_id' => $identityOther->id], [$fundRequestOther->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByCreatedAt(): void
    {
        $now = Carbon::now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequest1 = $this->makeFundRequestForIdentity($fund1, $identity);

        Carbon::setTestNow($now->copy()->addDays(5));
        $fundRequest2 = $this->makeFundRequestForIdentity($fund2, $identity);

        // assert "from date" filter
        $this->assertSearchIds([
            'from' => $now->format('Y-m-d'),
        ], [$fundRequest1->id, $fundRequest2->id], $employee);

        $this->assertSearchIds([
            'from' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$fundRequest2->id], $employee);

        // assert "to date" filter
        $this->assertSearchIds([
            'to' => $now->copy()->addDays(6)->format('Y-m-d'),
        ], [$fundRequest1->id, $fundRequest2->id], $employee);

        $this->assertSearchIds([
            'to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$fundRequest1->id], $employee);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $olderFundRequest = $this->makeFundRequestForIdentity($fund1, $identity);

        Carbon::setTestNow(now()->addDays(5));
        $newerFundRequest = $this->makeFundRequestForIdentity($fund2, $identity);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderFundRequest->id, $newerFundRequest->id], $employee);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerFundRequest->id, $olderFundRequest->id], $employee);
    }

    /**
     * @return void
     */
    public function testOrdersByFundName(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization, ['name' => 'A fund']);
        $fund2 = $this->makeTestFund($organization, ['name' => 'B fund']);

        $fundRequestA = $this->makeFundRequestForIdentity($fund1, $identity);
        $fundRequestB = $this->makeFundRequestForIdentity($fund2, $identity);

        $this->assertSearchOrder([
            'order_by' => 'fund_name',
            'order_dir' => 'asc',
        ], [$fundRequestA->id, $fundRequestB->id], $employee);

        $this->assertSearchOrder([
            'order_by' => 'fund_name',
            'order_dir' => 'desc',
        ], [$fundRequestB->id, $fundRequestA->id], $employee);
    }

    /**
     * @return void
     */
    public function testOrdersById(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequestA = $this->makeFundRequestForIdentity($fund1, $identity);
        $fundRequestB = $this->makeFundRequestForIdentity($fund2, $identity);

        $this->assertSearchOrder([
            'order_by' => 'id',
            'order_dir' => 'asc',
        ], [$fundRequestA->id, $fundRequestB->id], $employee);

        $this->assertSearchOrder([
            'order_by' => 'id',
            'order_dir' => 'desc',
        ], [$fundRequestB->id, $fundRequestA->id], $employee);
    }

    /**
     * @return void
     */
    public function testOrdersByState(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequestA = $this->makeFundRequestForIdentity($fund1, $identity);
        $fundRequestB = $this->makeFundRequestForIdentity($fund2, $identity);
        $fundRequestB->assignEmployee($employee)->approve();

        $this->assertSearchOrder([
            'order_by' => 'state',
            'order_dir' => 'asc',
        ], [$fundRequestA->id, $fundRequestB->id], $employee);

        $this->assertSearchOrder([
            'order_by' => 'state',
            'order_dir' => 'desc',
        ], [$fundRequestB->id, $fundRequestA->id], $employee);
    }

    /**
     * @return void
     */
    public function testOrdersByRequesterEmail(): void
    {
        $identityA = $this->makeIdentity('a@test.com');
        $identityA->primary_email->setVerified();
        $identityB = $this->makeIdentity('b@test.com');
        $identityB->primary_email->setVerified();

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequestA = $this->makeFundRequestForIdentity($fund1, $identityA);
        $fundRequestB = $this->makeFundRequestForIdentity($fund2, $identityB);

        $this->assertSearchOrder([
            'order_by' => 'requester_email',
            'order_dir' => 'asc',
        ], [$fundRequestA->id, $fundRequestB->id], $employee);

        $this->assertSearchOrder([
            'order_by' => 'requester_email',
            'order_dir' => 'desc',
        ], [$fundRequestB->id, $fundRequestA->id], $employee);
    }

    /**
     * @return void
     */
    public function testOrdersByAssigneeEmail(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employee = $organization->employees()->first();

        $employeeA = $organization->addEmployee($this->makeIdentity('a@test.com'));
        $employeeB = $organization->addEmployee($this->makeIdentity('b@test.com'));

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequestA = $this->makeFundRequestForIdentity($fund1, $identity)->assignEmployee($employeeA);
        $fundRequestB = $this->makeFundRequestForIdentity($fund2, $identity)->assignEmployee($employeeB);

        $this->assertSearchOrder([
            'order_by' => 'assignee_email',
            'order_dir' => 'asc',
        ], [$fundRequestA->id, $fundRequestB->id], $employee);

        $this->assertSearchOrder([
            'order_by' => 'assignee_email',
            'order_dir' => 'desc',
        ], [$fundRequestB->id, $fundRequestA->id], $employee);
    }

    /**
     * @return void
     */
    public function testOrdersByNote(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $fundRequestA = $this->makeFundRequestForIdentity($fund1, $identity);
        $fundRequestA->update(['note' => 'A note']);
        $fundRequestB = $this->makeFundRequestForIdentity($fund2, $identity);
        $fundRequestB->update(['note' => 'B note']);

        $this->assertSearchOrder([
            'order_by' => 'note',
            'order_dir' => 'asc',
        ], [$fundRequestA->id, $fundRequestB->id], $employee);

        $this->assertSearchOrder([
            'order_by' => 'note',
            'order_dir' => 'desc',
        ], [$fundRequestB->id, $fundRequestA->id], $employee);
    }

    /**
     * @param array $filters
     * @param Employee|null $employee
     * @return FundRequestSearch
     */
    private function makeSearch(array $filters, ?Employee $employee = null): FundRequestSearch
    {
        $search = new FundRequestSearch($filters, FundRequest::query());

        if ($employee) {
            $search->setEmployee($employee);
        }

        return $search;
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Employee|null $employee
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, ?Employee $employee = null): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $employee);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Employee|null $employee
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds, ?Employee $employee = null): void
    {
        $search = $this->makeSearch($filters, $employee);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
