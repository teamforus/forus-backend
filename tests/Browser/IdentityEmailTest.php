<?php

namespace Tests\Browser;

use App\Mail\User\IdentityEmailVerificationMail;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class IdentityEmailTest extends DuskTestCase
{
    use AssertsSentEmails;

    protected ?Identity $identity;
    protected ?string $link;
    protected ?string $titleSelector;
    protected ?string $title;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testIdentityEmailWebshopExample(): void
    {
        // Find first nijmegen implementation
        $implementation = Implementation::where('key', 'nijmegen')->first();

        // Models exist
        $this->assertNotNull($implementation);

        $this->link = $implementation->urlWebshop();
        $this->title = $implementation->name;
        $this->titleSelector = '@headerTitle';
        $this->identity = $this->makeIdentity(time() . "pr@example.com");
        $this->makeIdentityEmailTests();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testIdentityEmailDashboardExample(): void
    {
        // Find first nijmegen implementation and organization
        $implementation = Implementation::where('key', 'nijmegen')->first();
        $organization = Organization::where('name', 'Nijmegen')->first();

        // Models exist
        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        $this->link = $implementation->urlSponsorDashboard();
        $this->title = null;
        $this->titleSelector = '@fundsTitle';
        $this->identity = $organization->identity;
        $this->makeIdentityEmailTests();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    private function makeIdentityEmailTests(): void
    {
        $startTime = now();

        $this->browse(function (Browser $browser) use ($startTime) {
            // Visit the url and wait for the page to load
            $browser->visit($this->link);

            // Authorize identity
            $proxy = $this->makeIdentityProxy($this->identity, true, 'email_code');
            $browser->script("localStorage.setItem('active_account', '$proxy->access_token')");

            $browser->refresh();
            $this->goToIdentityEmailPage($browser);

            $email = $this->addNewEmail($browser);

            // Check if email exists in database
            $identityEmail = $this->identity->emails()->where('email', $email)->first();
            $this->assertNotNull($identityEmail);

            $this->resendEmailVerification($browser, $identityEmail, $startTime);

            // Verify email
            $browser->visit($this->findFirstEmailVerificationLink($identityEmail->email, $startTime));

            $this->goToIdentityEmailPage($browser);

            $this->setEmailAsPrimary($browser, $identityEmail);
            $this->deleteEmail($browser);
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    private function goToIdentityEmailPage(Browser $browser): void
    {
        $browser->waitFor($this->titleSelector);
        if ($this->title) {
            $browser->assertSeeIn($this->titleSelector, $this->title);
        }

        $browser->waitFor('@identityEmail');
        $browser->assertSeeIn('@identityEmail', $this->identity->email);

        $browser->waitFor('@identityEmail');
        $browser->element('@userProfile')->click();

        $browser->waitFor('@btnUserEmails');
        $browser->element('@btnUserEmails')->click();
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    private function deleteEmail(Browser $browser): void
    {
        $notPrimaryEmail = $this->identity->emails()->where('primary', false)->first();

        $browser->within('#email_' . $notPrimaryEmail->id, function(Browser $browser) {
            $browser->press('@btnDeleteIdentityEmail');
        });

        $browser->waitUntilMissing('#email_' . $notPrimaryEmail->id);
        $browser->assertNotPresent('#email_' . $notPrimaryEmail->id);

        $this->assertNull($this->identity->emails()->where('email', $notPrimaryEmail->email)->first());
    }

    /**
     * @param Browser $browser
     * @param IdentityEmail $identityEmail
     * @return void
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    private function setEmailAsPrimary(Browser $browser, IdentityEmail $identityEmail): void
    {
        $browser->waitFor('#email_' . $identityEmail->id);
        $browser->within('#email_' . $identityEmail->id, function(Browser $browser) use ($identityEmail) {
            $browser->assertSeeIn('@identityEmailListItemEmail', $identityEmail->email);
            $browser->assertNotPresent('@identityEmailListItemNotVerified');

            $browser->assertPresent('@identityEmailListItemSetPrimary');
            $browser->press('@identityEmailListItemSetPrimary');

            $browser->waitFor('@identityEmailListItemPrimary');
            $browser->assertPresent('@identityEmailListItemPrimary');
        });
    }

    /**
     * @param Browser $browser
     * @param IdentityEmail $identityEmail
     * @param Carbon $startTime
     * @return void
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    private function resendEmailVerification(
        Browser $browser,
        IdentityEmail $identityEmail,
        Carbon $startTime
    ): void {
        $browser->waitFor('#email_' . $identityEmail->id);
        $browser->within('#email_' . $identityEmail->id, function(Browser $browser) use ($identityEmail, $startTime) {
            $browser->assertSeeIn('@identityEmailListItemEmail', $identityEmail->email);
            $browser->press('@btnResendVerificationEmail');

            // Check if email resent
            $this->assertMailableSent($identityEmail->email, IdentityEmailVerificationMail::class, $startTime);
            $this->assertEmailVerificationLinkSent($identityEmail->email, $startTime);
        });
    }

    /**
     * @param Browser $browser
     * @return string
     * @throws TimeOutException
     */
    private function addNewEmail(Browser $browser): string
    {
        $browser->waitFor('@btnIdentityNewEmail');
        $browser->element('@btnIdentityNewEmail')->click();

        $browser->waitFor('@identityNewEmailForm');
        $browser->assertVisible('@identityNewEmailForm');

        $email = microtime(true) . "@example.com";
        // Type the email and submit the form for new email
        $browser->within('@identityNewEmailForm', function(Browser $browser) use ($email) {
            $browser->type('@identityNewEmailFormEmail', $this->identity->email);
            $browser->press('@identityNewEmailFormSubmit');

            $browser->waitFor('.form-error');
            $browser->assertVisible('.form-error');

            $browser->type('@identityNewEmailFormEmail', $email);
            $browser->press('@identityNewEmailFormSubmit');
        });

        $browser->waitFor('@identityNewEmailSuccess');
        $browser->assertVisible('@identityNewEmailSuccess');

        return $email;
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function logout(Browser $browser): void
    {
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnUserLogout');
        $browser->element('@btnUserLogout')->click();
    }
}
