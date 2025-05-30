<?php

namespace Tests\Browser;

use App\Mail\User\IdentityEmailVerificationMail;
use App\Models\Employee;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestIdentities;
use Throwable;

class IdentityEmailTest extends DuskTestCase
{
    use AssertsSentEmails;
    use MakesTestIdentities;
    use HasFrontendActions;

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdentityEmailsPageOnWebshop(): void
    {
        Cache::clear();

        $this->makeIdentityEmailTests(
            Implementation::byKey('nijmegen'),
            $this->makeIdentity($this->makeUniqueEmail('base-')),
            'webshop',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdentityEmailsPageOnSponsorDashboard(): void
    {
        Cache::clear();

        $this->makeIdentityEmailTests(
            Implementation::general(),
            $this->makeOrganizationIdentity(Organization::whereHas('funds')->first())->identity,
            'sponsor',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdentityEmailsPageOnProviderDashboard(): void
    {
        Cache::clear();

        $this->makeIdentityEmailTests(
            Implementation::general(),
            $this->makeOrganizationIdentity(Organization::whereHas('products')->first())->identity,
            'provider',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdentityEmailsPageOnValidatorDashboard(): void
    {
        Cache::clear();

        $this->makeIdentityEmailTests(
            Implementation::general(),
            $this->makeOrganizationIdentity(Organization::whereHas('funds')->first())->identity,
            'validator',
        );
    }

    /**
     * @param Organization $organization
     * @return Employee
     */
    private function makeOrganizationIdentity(Organization $organization): Employee
    {
        $identity = $this->makeIdentity();
        $identity->addEmail($this->makeUniqueEmail(), true, true, true);

        return $organization->addEmployee($identity, Role::pluck('id')->toArray());
    }

    /**
     * @param Implementation $implementation
     * @param Identity $identity
     * @param string $frontend
     * @throws Throwable
     * @return void
     */
    private function makeIdentityEmailTests(
        Implementation $implementation,
        Identity $identity,
        string $frontend
    ): void {
        $this->browse(function (Browser $browser) use ($implementation, $identity, $frontend) {
            // Visit the url and wait for the page to load
            $browser->visit($implementation->urlFrontend($frontend));

            // Authorize identity and go to identity emails page
            $this->loginIdentity($browser, $identity);
            $this->assertIdentityAuthenticatedFrontend($browser, $identity, $frontend);
            $this->goToIdentityEmailPage($browser);

            $email = $this->addNewEmail($browser, $identity);

            // Check if email exists in database
            $identityEmail = $identity->emails()->get()->where('email', $email)[0] ?? null;
            $this->assertNotNull($identityEmail);

            $startTime = now();
            $this->resendEmailVerification($browser, $identityEmail, $startTime);

            // Verify email
            $browser->visit($this->findFirstEmailVerificationLink($identityEmail->email, $startTime));
            $this->assertAndCloseSuccessNotification($browser);

            if ($frontend != 'webshop') {
                $browser->waitFor('@identityEmailConfirmedButton');
                $browser->element('@identityEmailConfirmedButton')->click();
            }

            $this->assertIdentityAuthenticatedFrontend($browser, $identity, $frontend);
            $this->goToIdentityEmailPage($browser);

            $this->setEmailAsPrimary($browser, $identityEmail);
            $this->deleteEmail($browser, $identity, $frontend);
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function goToIdentityEmailPage(Browser $browser): void
    {
        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();
        $browser->waitFor('@btnUserEmails');
        $browser->element('@btnUserEmails')->click();
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @param string $frontend
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function deleteEmail(Browser $browser, Identity $identity, string $frontend): void
    {
        /** @var IdentityEmail $notPrimaryEmail */
        $notPrimaryEmail = $identity->emails()->where('primary', false)->first();

        $browser->within('#email_' . $notPrimaryEmail->id, function (Browser $browser) {
            $browser->press('@btnDeleteIdentityEmail');
        });

        if ($frontend != 'webshop') {
            $browser->waitFor('@btnDangerZoneSubmit');
            $browser->press('@btnDangerZoneSubmit');
        }

        $browser->waitUntilMissing('#email_' . $notPrimaryEmail->id);
        $browser->assertNotPresent('#email_' . $notPrimaryEmail->id);

        $this->assertNull($identity->emails()->where('email', $notPrimaryEmail->email)->first());
        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @param IdentityEmail $identityEmail
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function setEmailAsPrimary(Browser $browser, IdentityEmail $identityEmail): void
    {
        $browser->waitFor('#email_' . $identityEmail->id);
        $browser->within('#email_' . $identityEmail->id, function (Browser $browser) use ($identityEmail) {
            $browser->assertSeeIn('@identityEmailListItemEmail', $identityEmail->email);
            $browser->assertNotPresent('@identityEmailListItemNotVerified');

            $browser->assertPresent('@identityEmailListItemSetPrimary');
            $browser->press('@identityEmailListItemSetPrimary');

            $browser->waitFor('@identityEmailListItemPrimary');
            $browser->assertPresent('@identityEmailListItemPrimary');
        });
        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @param IdentityEmail $identityEmail
     * @param Carbon $startTime
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @return void
     */
    private function resendEmailVerification(
        Browser $browser,
        IdentityEmail $identityEmail,
        Carbon $startTime
    ): void {
        $browser->waitFor('#email_' . $identityEmail->id);
        $browser->within('#email_' . $identityEmail->id, function (Browser $browser) use ($identityEmail, $startTime) {
            $browser->assertSeeIn('@identityEmailListItemEmail', $identityEmail->email);
            $browser->press('@btnResendVerificationEmail');
        });

        $this->assertAndCloseSuccessNotification($browser);

        // Check if email resent
        $this->assertMailableSent($identityEmail->email, IdentityEmailVerificationMail::class, $startTime);
        $this->assertEmailVerificationLinkSent($identityEmail->email, $startTime);
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @return string
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
        $browser->within('@identityNewEmailForm', function (Browser $browser) use ($email, $identity) {
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
}
