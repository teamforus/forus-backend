<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTest2FA;
use Tests\Traits\MakesTestIdentities;
use Throwable;

class EmployeeTest extends DuskTestCase
{
    use MakesTest2FA;
    use AssertsSentEmails;
    use HasFrontendActions;
    use MakesTestIdentities;

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmployeeCreate(): void
    {
        Cache::clear();

        $implementation = Implementation::byKey('nijmegen');

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
            $this->assertAndCloseSuccessNotification($browser);

            // Search the employee in the table by email
            $this->searchTable($browser, '@tableEmployee', $employee->identity->email, $employee->id);
            $this->checkEmployeePermissions($implementation->organization, $employee, $initialRole);

            // Change an employee role, test permissions and delete the employee
            $this->changeEmployeeRoles($browser, $employee, $updatedRole);
            $this->checkEmployeePermissions($implementation->organization, $employee, $updatedRole);

            $this->employeeDelete($browser, $employee);
            $this->assertAndCloseSuccessNotification($browser);

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmployeeDelete(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $this->browse(function (Browser $browser) use ($implementation) {
            $organization = $implementation->organization;

            $roles = Role::pluck('id')->toArray();
            $employee = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $roles);

            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $employee->identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $employee->identity);
            $this->selectDashboardOrganization($browser, $implementation->organization);

            // Go to employees list and add a new employee of initial role
            $this->goToEmployeesPage($browser);
            $this->searchTable($browser, '@tableEmployee', $employee->identity->email, $employee->id);

            $browser->waitFor("@tableEmployeeRow$employee->id");
            $browser->within("@tableEmployeeRow$employee->id", fn (Browser $b) => $b->press('@btnEmployeeMenu'));
            $browser->assertMissing("@btnEmployeeDelete$employee->id");

            // Logout
            $this->logout($browser);
            $employee->delete();
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmployee2FAState(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $employee = $implementation->organization->addEmployee(
            $this->makeIdentity($this->makeUniqueEmail()),
            Role::pluck('id')->toArray()
        );

        $this->browse(function (Browser $browser) use ($implementation, $employee) {
            $identity = $implementation->organization->identity;
            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);
            $this->selectDashboardOrganization($browser, $implementation->organization);

            // Go to employees list and add a new employee of initial role
            $this->goToEmployeesPage($browser);
            $this->searchTable($browser, '@tableEmployee', $employee->identity->email, $employee->id);

            // Assert 2fa is not configured
            $browser->waitFor("@notConfigured2fa$employee->id");
            $browser->assertMissing("@configured2fa$employee->id");

            // Activate 2fa
            $identityProxy = $this->makeIdentityProxy($employee->identity);
            $identity2FA = $this->setup2FAProvider($identityProxy, 'authenticator');
            $this->activate2FAProvider($identityProxy, $identity2FA);

            // Assert 2fa is configured
            $browser->refresh();
            $this->searchTable($browser, '@tableEmployee', $employee->identity->email, $employee->id);
            $browser->waitFor("@configured2fa$employee->id");
            $browser->assertMissing("@notConfigured2fa$employee->id");

            // Logout
            $this->logout($browser);
            $employee->delete();
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmployeeTransferOwnership(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $this->browse(function (Browser $browser) use ($implementation) {
            $role = Role::byKey('admin');
            $organization = $implementation->organization;
            $identity = $organization->identity;

            $browser->visit($implementation->urlSponsorDashboard());

            // Authorize identity
            $this->loginIdentity($browser, $identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);
            $this->selectDashboardOrganization($browser, $organization);

            // Go to employees list and add a new employee of initial role
            $this->goToEmployeesPage($browser);
            $employee = $this->createEmployee($browser, $organization, $role);
            $this->assertAndCloseSuccessNotification($browser);

            $this->transferOwnership($browser, $organization->findEmployee($identity), $employee);

            $organization->refresh();
            $this->assertFalse($organization->isOwner($identity));
            $this->assertTrue($organization->isOwner($employee->identity));

            // Logout
            $this->logout($browser);

            $organization->update(['identity_address' => $identity->address]);
            $employee->delete();
        });
    }

    /**
     * @param Browser $browser
     * @param Collection|Role[] $addRoles
     * @param Collection|Role[] $removeRoles
     * @param array $fields
     * @throws TimeoutException
     * @return void
     */
    protected function selectEmployeeRoles(
        Browser $browser,
        Collection|array $addRoles,
        Collection|array $removeRoles = [],
        array $fields = []
    ): void {
        $browser->waitFor('@formEmployeeEdit');

        // uncheck all previous employee roles and select the given one
        $browser->within('@formEmployeeEdit', function (Browser $browser) use ($addRoles, $removeRoles, $fields) {
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

        $browser->waitUntilMissing('@formEmployeeEdit');
    }

    /**
     * @param Browser $browser
     * @param Organization $organization
     * @param Role|Role[] $role
     * @throws TimeOutException
     * @return Employee
     */
    private function createEmployee(Browser $browser, Organization $organization, Role|array $role): Employee
    {
        $email = $this->makeUniqueEmail();
        $roles = is_array($role) ? $role : [$role];

        $browser->waitFor('@addEmployee');
        $browser->press('@addEmployee');

        $this->assertNotNull($role);

        $this->selectEmployeeRoles($browser, $roles, [], [
            '@formEmployeeEmail' => $email,
        ]);

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
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    private function changeEmployeeRoles(Browser $browser, Employee $employee, Role|array $role): void
    {
        $roles = is_array($role) ? $role : [$role];

        // Find and press employee edit button
        $browser->waitFor("@tableEmployeeRow$employee->id");
        $browser->within("@tableEmployeeRow$employee->id", fn (Browser $b) => $b->press('@btnEmployeeMenu'));

        $browser->waitFor("@btnEmployeeEdit$employee->id");
        $browser->press("@btnEmployeeEdit$employee->id");

        // Update employee roles
        $this->selectEmployeeRoles($browser, $roles, $employee->roles);

        // Wait for the form to be submitted
        $this->assertAndCloseSuccessNotification($browser);

        // Check that the new roles have been applied
        $employee->unsetRelation('roles');

        $invalidRoles = collect($roles)->pluck('id')->diff($employee->roles->pluck('id'));
        $this->assertTrue($invalidRoles->isEmpty(), 'Not all roles have been removed from the employee.');
    }

    /**
     * @param Browser $browser
     * @param Employee $employee
     * @throws TimeOutException
     * @return void
     */
    private function employeeDelete(Browser $browser, Employee $employee): void
    {
        $browser->waitFor("@tableEmployeeRow$employee->id");
        $browser->within("@tableEmployeeRow$employee->id", fn (Browser $b) => $b->press('@btnEmployeeMenu'));

        $browser->waitFor("@btnEmployeeDelete$employee->id");
        $browser->press("@btnEmployeeDelete$employee->id");

        $browser->waitFor('@modalDangerZone');
        $browser->waitFor('@btnDangerZoneSubmit');
        $browser->press('@btnDangerZoneSubmit');
        $browser->waitUntilMissing('@modalDangerZone');

        $this->assertNotNull($employee->identity->fresh());
        $this->assertTrue($employee->fresh()->trashed());
    }

    /**
     * @param Browser $browser
     * @param Employee $owner
     * @param Employee $employee
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function transferOwnership(Browser $browser, Employee $owner, Employee $employee): void
    {
        $this->assertOwner($browser, $owner, $employee);

        $this->searchTable($browser, '@tableEmployee', $owner->identity->email, $owner->id);
        $browser->waitFor("@tableEmployeeRow$owner->id");
        $browser->within("@tableEmployeeRow$owner->id", fn (Browser $b) => $b->press('@btnEmployeeMenu'));

        $browser->waitFor("@btnEmployeeTransferOwnership$owner->id");
        $browser->press("@btnEmployeeTransferOwnership$owner->id");

        $browser->waitFor('@modalTransferOrganizationOwnership');

        $browser->waitFor('@employeesSelect');
        $browser->click('@employeesSelect .select-control-search');
        $this->findOptionElement($browser, '@employeesSelect', $employee->identity->email)->click();

        $browser->press('@submitBtn');
        $browser->waitUntilMissing('@modalTransferOrganizationOwnership');

        $browser->refresh();

        $this->assertOwner($browser, $employee, $owner);
    }

    /**
     * @param Browser $browser
     * @param Employee $owner
     * @param Employee $prevOwner
     * @return void
     *@throws TimeoutException
     */
    private function assertOwner(Browser $browser, Employee $owner, Employee $prevOwner): void
    {
        // Assert previous employee is no longer owner
        $this->searchTable($browser, '@tableEmployee', $prevOwner->identity->email, $prevOwner->id);
        $browser->waitFor("@tableEmployeeRow$prevOwner->id");
        $browser->within("@tableEmployeeRow$prevOwner->id", fn (Browser $b) => $b->assertMissing("@owner$prevOwner->id"));

        // Assert the new employee is the new owner
        $this->searchTable($browser, '@tableEmployee', $owner->identity->email, $owner->id);
        $browser->waitFor("@tableEmployeeRow$owner->id");
        $browser->within("@tableEmployeeRow$owner->id", fn (Browser $b) => $b->assertVisible("@owner$owner->id"));
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
