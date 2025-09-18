<?php

namespace Browser\Exports;

use App\Exports\EventLogsExport;
use App\Models\Employee;
use App\Models\Implementation;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\DuskTestCase;
use Throwable;

class EventLogsExportTest extends DuskTestCase
{
    use ExportTrait;
    use HasFrontendActions;
    use NavigatesFrontendDashboard;

    /**
     * @throws Throwable
     * @return void
     */
    public function testEventLogsExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $roles = Role::pluck('id')->toArray();

        // create base employee (using it we will create new employee by api call)
        $employee = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $roles);

        // create new employee by api call to create log with base employee
        $newEmployee = $this->apiMakeEmployee($organization, [
            'email' => $this->makeUniqueEmail(),
            'roles' => $roles,
        ], $employee->identity);

        $this->browse(function (Browser $browser) use ($implementation, $employee, $newEmployee) {
            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $implementation->organization->identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
            $this->selectDashboardOrganization($browser, $implementation->organization);

            // Go to employees list, open export modal and assert all export fields in file
            $this->goToEventLogsPage($browser);
            $this->searchTable($browser, '@tableEventLogs', $employee->identity->email, $employee->logs()->first()->id);

            $fields = array_pluck(EventLogsExport::getExportFields(), 'name');

            foreach (static::FORMATS as $format) {
                // assert all fields exported
                $this->openFilterDropdown($browser);
                $data = $this->fillExportModalAndDownloadFile($browser, $format);
                $data && $this->assertFields($employee, $newEmployee, $data, $fields);

                // assert specific fields exported
                $this->openFilterDropdown($browser);

                $data = $this->fillExportModalAndDownloadFile($browser, $format, [
                    'created_at', 'loggable', 'event', 'identity_email',
                ]);

                $data && $this->assertFields($employee, $newEmployee, $data, [
                    EventLogsExport::trans('created_at'),
                    EventLogsExport::trans('loggable'),
                    EventLogsExport::trans('event'),
                    EventLogsExport::trans('identity_email'),
                ]);
            }

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @param Employee $employee
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        Employee $employee,
        Employee $newEmployee,
        array $rows,
        array $fields
    ): void {
        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert that employee exists in logs
        $this->assertStringContainsString("#$newEmployee->id", $rows[1][1]);
        $this->assertStringContainsString($newEmployee->identity->email, $rows[1][2]);
        $this->assertEquals($employee->identity->email, $rows[1][3]);
    }
}
