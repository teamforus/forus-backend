<?php

namespace Tests\Browser\Exports;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Implementation;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Throwable;

class EmployeesExportTest extends DuskTestCase
{
    use ExportTrait;
    use HasFrontendActions;

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmployeeExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $employee = $implementation->organization->employees()->first();
        $this->assertNotNull($employee);

        $this->browse(function (Browser $browser) use ($implementation, $employee) {
            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $implementation->organization->identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
            $this->selectDashboardOrganization($browser, $implementation->organization);

            // Go to employees list, open export modal and assert all export fields in file
            $this->goToEmployeesPage($browser);
            $this->searchTable($browser, '@tableEmployee', $employee->identity->email, $employee->id);

            $fields = $this->getExportFields();

            foreach (static::FORMATS as $format) {
                // assert all fields exported
                $data = $this->fillExportModalAndDownloadFile($browser, $format);
                $data && $this->assertFields($employee, $data, $fields);

                // assert specific fields exported
                $data = $this->fillExportModalAndDownloadFile($browser, $format, ['email']);
                $data && $this->assertFields($employee, $data, [EmployeesExport::trans('email')]);
            }

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @return array
     */
    protected function getExportFields(): array
    {
        // Filter headers except roles header and add all roles
        $fields = array_pluck(EmployeesExport::getExportFields(), 'name');
        $fields = array_filter($fields, fn ($field) => $field !== EmployeesExport::trans('roles'));
        $roles = Role::with('translations')->get()->pluck('name')->toArray();

        return [...$fields, ...$roles];
    }

    /**
     * @param Employee $employee
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        Employee $employee,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);
        $this->assertEquals($employee->identity->email, $rows[1][0]);
    }
}
