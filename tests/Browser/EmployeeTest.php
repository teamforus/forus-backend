<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Exception\TimeOutException;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestIdentities;

class EmployeeTest extends DuskTestCase
{
    use AssertsSentEmails, MakesTestIdentities, HasFrontendActions;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testEmployeeCreate(): void
    {
        Cache::clear();

        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $this->browse(function (Browser $browser) use ($implementation) {
            $initialRole = Role::byKey('finance');
            $updatedRole = Role::byKey('validation');

            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $implementation->organization->identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $implementation->organization->identity);
            $this->selectDashboardOrganization($browser, $implementation->organization);

            // Go to employees list and add a new employee of initial role
            $this->goToEmployeesPage($browser);
            $employee = $this->createEmployee($browser, $implementation->organization, $initialRole);

            // Search the employee in the table by email
            $this->searchEmployee($browser, $employee);
            $this->checkEmployeePermissions($implementation->organization, $employee, $initialRole);

            // Change employee role, test permissions and delete the employee
            $this->changeEmployeeRoles($browser, $employee, $updatedRole);
            $this->checkEmployeePermissions($implementation->organization, $employee, $updatedRole);
            $this->employeeDelete($browser, $employee);

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @param Organization $organization
     * @param Role|Role[] $role
     * @return Employee
     * @throws TimeOutException
     */
    private function createEmployee(Browser $browser, Organization $organization, Role|array $role): Employee
    {
        $email = $this->makeUniqueEmail();
        $roles = is_array($role) ? $role : [$role];

        $browser->waitFor('@addEmployee', 10);
        $browser->press('@addEmployee');

        $this->assertNotNull($role);

        $this->selectEmployeeRoles($browser, $roles, [], [
            '@formEmployeeEmail' => $email,
        ]);

        $browser->waitUntilMissing('@successNotification');

        $browser->pause(1000);

        $identity = Identity::findByEmail($email);
        $this->assertNotNull($identity);

        $employee = $organization->findEmployee($identity->address);
        $this->assertNotNull($employee);

        return $employee;
    }

    /**
     * @param Browser $browser
     * @param Employee $employee
     * @param Role|Role[] $role
     * @return void
     * @throws TimeOutException
     */
    private function changeEmployeeRoles(Browser $browser, Employee $employee, Role|array $role): void
    {
        $roles = is_array($role) ? $role : [$role];

        // Find and press employee edit button
        $browser->waitFor("@employeeRow$employee->id");
        $browser->within("@employeeRow$employee->id", fn(Browser $b) => $b->press("@btnEmployeeEdit"));

        // Update employee roles
        $this->selectEmployeeRoles($browser, $roles, $employee->roles);

        // Wait for the form to be submitted
        $browser->waitFor('@successNotification');
        $browser->waitUntilMissing('@successNotification');

        $browser->pause(1000);

        // Check that the new roles have been applied
        $employee->unsetRelation('roles');

        $invalidRoles = collect($roles)->pluck('id')->diff($employee->roles->pluck('id'));
        $this->assertTrue($invalidRoles->isEmpty(), 'Not all roles have been removed from the employee.');
    }

    /**
     * @param Browser $browser
     * @param Collection|Role[] $addRoles
     * @param Collection|Role[] $removeRoles
     * @param array $fields
     * @return void
     * @throws TimeOutException
     */
    protected function selectEmployeeRoles(
        Browser $browser,
        Collection|array $addRoles,
        Collection|array $removeRoles = [],
        array $fields = []
    ): void {
        $browser->waitFor('@formEmployeeEdit');

        // uncheck all previous employee roles and select the given one
        $browser->within('@formEmployeeEdit', function(Browser $browser) use ($addRoles, $removeRoles, $fields) {
            foreach ($removeRoles as $role) {
                $browser->waitFor("label[for=role_$role->id]");
                $browser->press("label[for=role_$role->id]");
            }

            foreach ($addRoles as $role) {
                $browser->waitFor("label[for=role_$role->id]");
                $browser->press("label[for=role_$role->id]");
            }

            foreach ($fields as $fieldKey => $fieldValue) {
                $browser->type($fieldKey, $fieldValue);
            }

            $browser->press('@formEmployeeSubmit');
        });
    }

    /**
     * @param Browser $browser
     * @param Employee $employee
     * @return void
     * @throws TimeOutException
     */
    private function employeeDelete(Browser $browser, Employee $employee): void
    {
        $browser->waitFor("@employeeRow$employee->id");
        $browser->within("@employeeRow$employee->id", function(Browser $browser) {
            $browser->press("@btnEmployeeDelete");
        });

        $browser->waitFor('@modalDangerZone');
        $browser->waitFor('@btnDangerZoneSubmit');
        $browser->press('@btnDangerZoneSubmit');
        $browser->waitFor('@successNotification');
        $browser->waitUntilMissing('@successNotification');

        $this->assertNotNull($employee->identity->fresh());
        $this->assertTrue($employee->fresh()->trashed());
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function goToEmployeesPage(Browser $browser): void
    {
        $browser->waitFor('@employeesPage');
        $browser->element('@employeesPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Employee $employee
     * @return void
     * @throws TimeOutException
     */
    private function searchEmployee(Browser $browser, Employee $employee): void
    {
        $browser->waitFor('@searchEmployee');
        $browser->type('@searchEmployee', $employee->identity->email);

        $browser->pause(2000);
        $browser->waitFor('@employeeEmail');
        $browser->assertSeeIn('@employeeEmail', $employee->identity->email);
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @param Role $role
     * @return void
     */
    private function checkEmployeePermissions(Organization $organization, Employee $employee, Role $role): void
    {
        foreach ($role->permissions as $permission) {
            $this->assertTrue($organization->identityCan($employee->identity, $permission->key));
        }
    }
}
