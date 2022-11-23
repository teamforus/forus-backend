<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Support\Carbon;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Exception\TimeOutException;
use Tests\DuskTestCase;

class EmployeeTest extends DuskTestCase
{
    use AssertsSentEmails;

    protected ?Identity $identity;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testEmployeeCreate(): void
    {
        $startTime = now();

        $implementation = Implementation::where('key', 'general')->first();
        $organization = Organization::where('name', 'Nijmegen')->first();

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        $this->identity = $organization->identity;
        $link = $implementation->urlSponsorDashboard();

        $this->browse(function (Browser $browser) use ($link, $implementation, $startTime) {
            $browser->visit($link);

            // Authorize identity
            $proxy = $this->makeIdentityProxy($this->identity);
            $browser->script("localStorage.setItem('active_account', '$proxy->access_token')");

            $browser->waitFor('@headerOrganizationSwitcher');
            $this->createEmployee($browser, $startTime);

            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @param Carbon $startTime
     * @return void
     * @throws TimeOutException
     */
    private function createEmployee(Browser $browser, Carbon $startTime): void
    {
        $this->goToEmployeesPage($browser);

        $browser->waitFor('@addEmployee');
        $browser->press('@addEmployee');

        $role  = Role::first();
        $email = 'test'. time() .'@example.com';

        $browser->within('@formEmployeeEdit', function(Browser $browser) use ($role, $email) {
            $browser->click('label[for="role_'. $role->id .'"]');
            $browser->type('@formEmployeeEmail', $email);
            $browser->press('@formEmployeeSubmit');
        });

        $this->searchEmployee($browser, $email, $startTime);
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function goToEmployeesPage(Browser $browser): void
    {
        $browser->waitFor('@identityEmail');
        $browser->assertSeeIn('@identityEmail', $this->identity->email);

        $browser->waitFor('@employeesPage');
        $browser->element('@employeesPage')->click();
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function logout(Browser $browser): void
    {
        $browser->refresh();

        $browser->waitFor('@authUserMenu');
        $browser->element('@authUserMenu')->click();

        $browser->waitFor('@authUserLogout');
        $browser->element('@authUserLogout')->click();
    }

    /**
     * @param Browser $browser
     * @param string $email
     * @param Carbon $startTime
     * @return void
     * @throws TimeOutException
     */
    private function searchEmployee(Browser $browser, string $email, Carbon $startTime): void
    {
        $browser->waitFor('@searchEmployee');
        $browser->type('@searchEmployee', $email);

        $browser->pause(1000);
        $browser->waitFor('@employeeEmail');
        $browser->assertSeeIn('@employeeEmail', $email);

        Employee::where(
            'identity_address', Identity::findByEmail($email)->address,
        )->where(
            'created_at', '>=', $startTime
        )->delete();
    }
}
