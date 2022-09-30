<?php

namespace Tests\Browser;

use App\Mail\Auth\UserLoginMail;
use App\Mail\User\EmailActivationMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use AssertsSentEmails;

    /**
     * A Dusk test example.
     *
     * @return void
     * @throws \Throwable
     */
    public function testSignInByEmailExample(): void
    {
        $startTime = now();

        $this->browse(function (Browser $browser) use ($startTime) {
            // Find first existing user and nijmegen implementation
            $identity = Identity::first();
            $implementation = Implementation::where('key', 'nijmegen')->first();

            // Implementation and Identity exist
            $this->assertNotNull($identity);
            $this->assertNotNull($implementation);

            // Visit the implementation webshop and wait for the page to load
            $browser->visit($implementation->urlWebshop());
            $browser->waitFor('@header');
            $browser->assertSeeIn('@headerTitle', $implementation->name);

            // Click on the navbar start button to go to the auth page
            $browser->element('@btnStart')->click();
            $browser->waitFor('@authOptionEmailRestore');

            // Select the login by option
            $browser->element('@authOptionEmailRestore')->click();
            $browser->assertVisible('@authEmailForm');

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
            $browser->visit($this->findFirstEmailRestoreLink($identity->email, $startTime));
            $browser->waitFor('@identityEmail');
            $browser->assertSeeIn('@identityEmail', $identity->email);

            // Logout identity
            $browser->element('@userProfile')->click();
            $browser->waitFor('@btnUserLogout');
            $browser->element('@btnUserLogout')->click();
        });
    }


    /**
     * A Dusk test example.
     *
     * @return void
     * @throws \Throwable
     */
    public function testSignUpByEmailExample(): void
    {
        $startTime = now();

        $this->browse(function (Browser $browser) use ($startTime) {
            // Find nijmegen implementation and define target email
            $email = microtime(true) . "@example.com";
            $implementation = Implementation::where('key', 'nijmegen')->first();

            // Implementation exist
            $this->assertNotNull($implementation);

            // Visit the implementation webshop and wait for the page to load
            $browser->visit($implementation->urlWebshop());
            $browser->waitFor('@header');
            $browser->assertSeeIn('@headerTitle', $implementation->name);

            // Click on the navbar start button to go to the auth page
            $browser->element('@btnStart')->click();
            $browser->waitFor('@authOptionEmailRegister');

            // Select the registration by email option
            $browser->element('@authOptionEmailRegister')->click();
            $browser->assertVisible('@authEmailForm');

            // Type the email and submit the form
            $browser->within('@authEmailForm', function(Browser $browser) use ($email) {
                $browser->type('@authEmailFormEmail', $email);
                $browser->press('@authEmailFormSubmit');
            });

            // Await for email sent confirmation screen
            $browser->waitFor('@authEmailSentConfirmation');
            $browser->assertVisible('@authEmailSentConfirmation');

            // Check that the confirmation link was sent to the user by email
            $this->assertMailableSent($email, EmailActivationMail::class, $startTime);
            $this->assertEmailConfirmationLinkSent($email, $startTime);

            // Get and follow the auth link from the email then check if the user is authenticated
            $browser->visit($this->findFirstEmailConfirmationLink($email, $startTime));
            $browser->waitFor('@identityEmail');
            $browser->assertSeeIn('@identityEmail', $email);

            // Logout identity
            $browser->element('@userProfile')->click();
            $browser->waitFor('@btnUserLogout');
            $browser->element('@btnUserLogout')->click();
        });
    }
}
