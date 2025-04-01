<?php

namespace Tests\Browser\Exports;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Implementation;
use App\Models\Role;
use Tests\Browser\Traits\ExportTrait;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
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

        /** @var Employee $employee */
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
            $this->searchEmployee($browser, $employee);
            $this->fillExportModal($browser);

            $csvData = $this->parseCsvFile();

            $fields = $this->getExportFields();
            $this->assertFields($employee, $csvData, $fields);

            // Open export modal, select specific fields and assert it
            $this->fillExportModal($browser, ['email']);
            $csvData = $this->parseCsvFile();

            $this->assertFields($employee, $csvData, [
                EmployeesExport::trans('email'),
            ]);

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeOutException
     * @return void
     */
    protected function goToEmployeesPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupOrganization');
        $browser->element('@asideMenuGroupOrganization')->click();
        $browser->waitFor('@employeesPage');
        $browser->element('@employeesPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Employee $employee
     * @return void
     * @throws TimeoutException
     */
    protected function searchEmployee(Browser $browser, Employee $employee): void
    {
        $browser->waitFor('@searchEmployee');
        $browser->type('@searchEmployee', $employee->identity->email);

        $browser->waitFor("@employeeRow$employee->id", 20);
        $browser->assertVisible("@employeeRow$employee->id");

        $browser->waitUntil("document.querySelectorAll('#employeesTable tbody tr').length === 1");
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
