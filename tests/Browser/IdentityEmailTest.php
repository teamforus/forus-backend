<?php

namespace Tests\Browser;

use App\Mail\User\IdentityEmailVerificationMail;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestIdentities;

class IdentityEmailTest extends DuskTestCase
{
    use AssertsSentEmails, MakesTestIdentities;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testIdentityEmailWebshopExample(): void
    {
        Cache::clear();

        $this->makeIdentityEmailTests(
            Implementation::byKey('nijmegen'),
            $this->makeIdentity($this->makeUniqueEmail('base-')),
            'webshop'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testIdentityEmailSponsorDashboardExample(): void
    {
        Cache::clear();

        $this->makeIdentityEmailTests(
            Implementation::general(),
            Organization::whereHas('funds')->first()->identity,
            'sponsor'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testIdentityEmailProviderDashboardExample(): void
    {
        Cache::clear();

        $this->makeIdentityEmailTests(
            Implementation::general(),
            Organization::whereHas('products')->first()->identity,
            'provider'
        );
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testIdentityEmailValidatorDashboardExample(): void
    {
        Cache::clear();

        $this->makeIdentityEmailTests(
            Implementation::general(),
            Organization::whereHas('funds')->first()->identity,
            'validator'
        );
    }

    /**
     * @param Implementation $implementation
     * @param Identity $identity
     * @param string $frontend
     * @return void
     * @throws \Throwable
     */
    private function makeIdentityEmailTests(
        Implementation $implementation,
        Identity $identity,
        string $frontend
    ): void {
        $this->browse(function (Browser $browser) use ($implementation, $identity, $frontend) {
            $proxy = $this->makeIdentityProxy($identity, true, 'email_code');

            // Visit the url and wait for the page to load
            $browser->visit($implementation->urlFrontend($frontend));

            // Authorize identity
            $this->applyIdentityProxy($browser, $proxy);
            $browser->pause( 5000);

            $this->goToIdentityEmailPage($browser, $identity);
            $browser->pause( 5000);

            $email = $this->addNewEmail($browser, $proxy->identity);

            // Check if email exists in database
            /** @var IdentityEmail $identityEmail */
            $identityEmail = $identity->emails()->where('email', $email)->first();
            $this->assertNotNull($identityEmail);

            $startTime = now();
            $this->resendEmailVerification($browser, $identityEmail, $startTime);

            // Verify email
            $browser->visit($this->findFirstEmailVerificationLink($identityEmail->email, $startTime));
            $browser->pause( 5000);

            $this->goToIdentityEmailPage($browser, $identity);
            $browser->pause( 5000);

            $this->setEmailAsPrimary($browser, $identityEmail);
            $this->deleteEmail($browser, $identity);
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     * @throws TimeOutException
     */
    private function goToIdentityEmailPage(Browser $browser, Identity $identity): void
    {
        $browser->pause(2000);

        if (!empty($browser->element('@identityEmailConfirmedButton'))) {
            $browser->element('@identityEmailConfirmedButton')->click();
        }

        $browser->waitFor('@identityEmail');
        $browser->assertSeeIn('@identityEmail', $identity->email);

        $browser->waitFor('@identityEmail');
        $browser->element('@userProfile')->click();

        $browser->waitFor('@btnUserEmails');
        $browser->element('@btnUserEmails')->click();
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return void
     * @throws TimeOutException
     */
    private function deleteEmail(Browser $browser, Identity $identity): void
    {
        $notPrimaryEmail = $identity->emails()->where('primary', false)->first();

        $browser->within('#email_' . $notPrimaryEmail->id, function(Browser $browser) {
            $browser->press('@btnDeleteIdentityEmail');
        });

        $browser->waitUntilMissing('#email_' . $notPrimaryEmail->id);
        $browser->assertNotPresent('#email_' . $notPrimaryEmail->id);

        $this->assertNull($identity->emails()->where('email', $notPrimaryEmail->email)->first());
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
        });

        $browser->waitFor('@successNotification');

        // Check if email resent
        $this->assertMailableSent($identityEmail->email, IdentityEmailVerificationMail::class, $startTime);
        $this->assertEmailVerificationLinkSent($identityEmail->email, $startTime);
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @return string
     * @throws TimeOutException
     */
    private function addNewEmail(Browser $browser, Identity $identity): string
    {
        $startTime = now();
        $email = $this->makeUniqueEmail();

        $browser->waitFor('@btnIdentityNewEmail');
        $browser->element('@btnIdentityNewEmail')->click();

        $browser->waitFor('@identityNewEmailForm');
        $browser->assertVisible('@identityNewEmailForm');

        // Type the email and submit the form for new email
        $browser->within('@identityNewEmailForm', function(Browser $browser) use ($email, $identity) {
            $browser->type('@identityNewEmailFormEmail', $identity->email);
            $browser->press('@identityNewEmailFormSubmit');

            $browser->waitFor('.form-error');
            $browser->assertVisible('.form-error');

            $browser->type('@identityNewEmailFormEmail', $email);
            $browser->press('@identityNewEmailFormSubmit');
        });

        $browser->waitFor('@identityNewEmailSuccess');
        $browser->assertVisible('@identityNewEmailSuccess');

        $this->assertMailableSent($email, IdentityEmailVerificationMail::class, $startTime);
        $this->assertEmailVerificationLinkSent($email, $startTime);

        return $email;
    }

    /**
     * @param Browser $browser
     * @param IdentityProxy $proxy
     * @return void
     */
    protected function applyIdentityProxy(Browser $browser, IdentityProxy $proxy): void
    {
        $browser->script("localStorage.setItem('active_account', '$proxy->access_token')");
        $browser->refresh();
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
