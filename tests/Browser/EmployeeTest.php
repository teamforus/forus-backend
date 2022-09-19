<?php

namespace Tests\Browser;

use App\Mail\Auth\UserLoginMail;
use App\Models\Employee;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Role;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EmployeeTest extends DuskTestCase
{
    use AssertsSentEmails;

    /**
     * Test employee creation
     *
     * @return void
     * @throws \Throwable
     */
    public function testEmployeeCreate(): void
    {
        $this->browse(function (Browser $browser) {
            $identity = $this->visitEmployeesPage();

            $browser->within('@formEmployeeCreate', function(Browser $browser) use ($identity) {
                $browser->type('@authEmailFormEmail', $identity->email);
                $browser->press('@authEmailFormSubmit');
            });

            $browser->assertSeeIn('@employeeEmail', $identity->email);
        });
    }

    /**
     * Test employee update
     *
     * @return void
     * @throws \Throwable
     */
    public function testEmployeeUpdate(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->press('@employeesEdit');

            $browser->within('@formEmployeeEdit', function(Browser $browser){
                $browser->type('@role', Role::first());
                $browser->press('@authEmailFormSubmit');
            });

            $browser->assertSeeIn('@employeeRole', Role::first());
        });
    }

    /**
     * Test employee delete
     *
     * @return void
     * @throws \Throwable
     */
    public function testEmployeeDelete(): void
    {
        $this->browse(function (Browser $browser) {
            $identity = $this->visitEmployeesPage();
            $browser->press('@formEmployeeDelete');

            $browser->assertNotPresent('@employeeEmail', $identity->email);
        });
    }

    /**
     * @return Identity
     * @throws \Throwable
     */
    protected function visitEmployeesPage(): Identity
    {
        $identity = null;

        $this->browse(function (Browser $browser) use (&$identity) {
            // Find first existing user and nijmegen implementation
            $identity = Identity::first();
            $implementation = Implementation::where('key', 'nijmegen')->first();

            // Implementation and Identity exist
            $this->assertNotNull($identity);
            $this->assertNotNull($implementation);

            // Visit the implementation sponsor dashboard and wait for the page to load
            $browser->visit($implementation->urlSponsorDashboard());
            $this->signInUser($identity);

            $browser->click('@employeesPage');
            $browser->waitFor('@formEmployeeEdit');
        });

        return $identity;
    }

    /**
     * @param Identity $identity
     * @return void
     * @throws \Throwable
     */
    protected function signInUser(Identity $identity): void
    {
        $startTime = now();

        $this->browse(function (Browser $browser) use ($startTime, $identity) {
            // Type the email and submit the form
            $browser->within('@authEmailForm', function(Browser $browser) use ($identity) {
                $browser->type('@authEmailFormEmail', $identity->email);
                $browser->press('@authEmailFormSubmit');
            });

            // Await for email sent confirmation screen
            $browser->waitFor('@authEmailSentConfirmation');
            $browser->assertVisible('@authEmailSentConfirmation');

            // Check that the confirmation link was sent to the user by email
            $this->assertMailableSent($identity->email, UserLoginMail::class, $startTime);
            $this->assertEmailRestoreLinkSent($identity->email, $startTime);

            // Get and follow the auth link from the email then check if the user is authenticated
            $browser->visit($this->findFirstEmalRestoreLink($identity->email, $startTime));
            $browser->waitFor('@identityEmail');
            $browser->assertSeeIn('@identityEmail', $identity->email);
        });
    }
}
