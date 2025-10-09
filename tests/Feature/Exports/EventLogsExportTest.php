<?php

namespace Feature\Exports;

use App\Exports\EventLogsExport;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class EventLogsExportTest extends TestCase
{
    use BaseExport;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/logs/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testEventLogsExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $roles = Role::pluck('id')->toArray();

        // create base employee (using it we will create new employee by api call)
        $employee = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $roles);

        // create new employee by api call to create log with base employee
        $newEmployee = $this->apiMakeEmployee($organization, [
            'email' => $this->makeUniqueEmail(),
            'roles' => $roles,
        ], $employee->identity);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $baseQuery = [
            'data_format' => 'csv',
            'q' => $employee->identity->email,
            'loggable' => ['fund', 'bank_connection', 'employees'],
        ];

        // Assert export without fields - must be all fields by default
        $response = $this->getJson(
            sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query($baseQuery),
            $apiHeaders
        );

        $fields = array_pluck(EventLogsExport::getExportFields(), 'name');
        $this->assertFields($response, $employee, $newEmployee, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            ...$baseQuery,
            'fields' => EventLogsExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertFields($response, $employee, $newEmployee, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            ...$baseQuery,
            'fields' => ['created_at', 'loggable', 'event', 'identity_email'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertFields($response, $employee, $newEmployee, [
            EventLogsExport::trans('created_at'),
            EventLogsExport::trans('loggable'),
            EventLogsExport::trans('event'),
            EventLogsExport::trans('identity_email'),
        ]);
    }

    /**
     * @param TestResponse $response
     * @param Employee $employee
     * @param Employee $newEmployee
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        Employee $employee,
        Employee $newEmployee,
        array $fields
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert that employee exists in logs
        $this->assertStringContainsString("#$newEmployee->id", $rows[1][1]);
        $this->assertStringContainsString($newEmployee->identity->email, $rows[1][2]);
        $this->assertEquals($employee->identity->email, $rows[1][3]);
    }
}
