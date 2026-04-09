<?php

namespace Tests\Feature\Exports;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
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
        $this->assertExportedData($response, $employee, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => EmployeesExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $employee, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['email'],
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $employee, [EmployeesExport::trans('email')]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmployeesExportKeepsColumnsWithSameVisibleLabel(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();
        $this->assertNotNull($employee);

        $duplicateLabel = 'duplicate role';
        $locale = app()->getLocale();

        $roleA = Role::create(['key' => token_generator()->generate(16)]);
        $roleA->translations()->create([
            'locale' => $locale,
            'name' => $duplicateLabel,
            'description' => $duplicateLabel,
        ]);

        $roleB = Role::create(['key' => token_generator()->generate(16)]);
        $roleB->translations()->create([
            'locale' => $locale,
            'name' => $duplicateLabel,
            'description' => $duplicateLabel,
        ]);

        $employee->roles()->syncWithoutDetaching([$roleA->id]);
        $employee->load('roles');

        $response = $this->getJson(
            sprintf($this->apiExportUrl, $organization->id) . '?data_format=csv',
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $indexes = array_keys($rows[0], $duplicateLabel, true);
        $values = array_map(fn (int $index) => $rows[1][$index], $indexes);

        $this->assertCount(2, $indexes);
        $this->assertEqualsCanonicalizing(['ja', 'nee'], $values);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmployeesExportKeepsCanonicalFieldOrderWhenSelectedFieldsAreReordered(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $employee = $organization->employees()->first();
        $this->assertNotNull($employee);

        $response = $this->getJson(
            sprintf($this->apiExportUrl, $organization->id) . '?' . http_build_query([
                'data_format' => 'csv',
                'fields' => ['roles', 'owner', 'email'],
            ]),
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        $this->assertEquals(EmployeesExport::trans('email'), $rows[0][0]);
        $this->assertEquals(EmployeesExport::trans('owner'), $rows[0][1]);
        $this->assertEquals($employee->identity->email, $rows[1][0]);
    }

    /**
     * @return array
     */
    protected function getExportFields(): array
    {
        $fields = Arr::pluck(EmployeesExport::getExportFields(), 'name');
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
    protected function assertExportedData(
        TestResponse $response,
        Employee $employee,
        array $fields
    ): void {
        $rows = $this->assertCsvExportResponse($response);

        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $employee->identity->email, 0);
    }
}
