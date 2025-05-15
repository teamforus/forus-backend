<?php

namespace Tests\Feature\Exports;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class EmployeesExportTest extends TestCase
{
    use BaseExport;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/employees/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmployeesExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();
        $this->assertNotNull($employee);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->getJson(
            sprintf($this->apiExportUrl, $organization->id) . '?data_format=csv',
            $apiHeaders
        );

        // Filter headers except roles header and add all roles
        $fields = $this->getExportFields();
        $this->assertFields($response, $employee, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => EmployeesExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertFields($response, $employee, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['email'],
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertFields($response, $employee, [EmployeesExport::trans('email')]);
    }

    /**
     * @return array
     */
    protected function getExportFields(): array
    {
        $fields = array_pluck(EmployeesExport::getExportFields(), 'name');
        $fields = array_filter($fields, fn ($field) => $field !== EmployeesExport::trans('roles'));
        $roles = Role::with('translations')->get()->pluck('name')->toArray();

        return [...$fields, ...$roles];
    }

    /**
     * @param TestResponse $response
     * @param Employee $employee
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        Employee $employee,
        array $fields
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert that email in file equals to employee email
        $this->assertEquals($employee->identity->email, $rows[1][0]);
    }
}
