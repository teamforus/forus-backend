<?php

namespace Tests\Browser;

use App\Mail\Auth\UserLoginMail;
use App\Mail\User\EmailActivationMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestIdentities;

class AuthenticationTest extends DuskTestCase
{
    use AssertsSentEmails, MakesTestIdentities, HasFrontendActions;

    /**
     * A Dusk test example.
     *
     * @return void
     * @throws \Throwable
     */
    public function testSignInByEmailExample(): void
    {
        Cache::clear();
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
            $browser->waitFor('@authEmailForm');
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
            $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

            // Logout identity
            $this->logout($browser);
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
        Cache::clear();
        $startTime = now();

        $this->browse(function (Browser $browser) use ($startTime) {
            // Find nijmegen implementation and define target email
            $email = $this->makeUniqueEmail();
            $implementation = Implementation::where('key', 'nijmegen')->first();

            // Implementation exist
            $this->assertNotNull($implementation);

            // Visit the implementation webshop and wait for the page to load
            $browser->visit($implementation->urlWebshop());
            $browser->waitFor('@header', 10);
            $browser->assertSeeIn('@headerTitle', $implementation->name);

            // Click on the navbar start button to go to the auth page
            $browser->element('@btnStart')->click();
            $browser->waitFor('@authOptionEmailRegister');

            // Select the registration by email option
            $browser->element('@authOptionEmailRegister')->click();
            $browser->waitFor('@authEmailForm');
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
            $browser->waitFor('#main-content');

            // Logout identity
            $this->logout($browser);
        });
    }
}
