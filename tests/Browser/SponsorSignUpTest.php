<?php

namespace Tests\Browser;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Support\Carbon;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\HasSignUpActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class SponsorSignUpTest extends DuskTestCase
{
    use AssertsSentEmails;
    use HasFrontendActions;
    use HasSignUpActions;
    use MakesTestIdentities;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::closeAll();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorSignupEmailFlow(): void
    {
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        $this->browse(function (Browser $browser) use ($startTime, $implementation) {
            $browser->visit($implementation->urlSponsorDashboard('aanmelden'));
            $email = $this->makeUniqueEmail('sponsor');

            $this->signUpWithEmail($browser, $email, $startTime);

            // identity has no organizations yet, so the organization select step is skipped
            $this->goToOrganizationAdd($browser);
            $orgName = $this->fillSignUpOrganizationForm($browser);
            $this->nextSignUpStep($browser);

            $this->finishSignUp($browser);

            $identity = Identity::findByEmail($email);
            $this->assertNotNull($identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);

            $organization = Organization::where('identity_address', $identity->address)->first();

            $this->assertNotNull($organization);
            $this->assertEquals($orgName, $organization->name);
            $this->assertTrue($organization->email_public);
            $this->assertTrue($organization->phone_public);
            $this->assertTrue($organization->website_public);

            $this->logout($browser);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorSignupSelectExistingOrganization(): void
    {
        $implementation = Implementation::where('key', 'nijmegen')->first();
        $identity = $this->makeIdentity($this->makeUniqueEmail('sponsor'));
        $organization = $this->makeTestOrganization($identity);

        $this->browse(function (Browser $browser) use ($implementation, $identity, $organization) {
            $browser->visit($implementation->urlSponsorDashboard());
            $this->loginIdentity($browser, $identity);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);

            $browser->visit($implementation->urlSponsorDashboard('aanmelden'));

            $browser
                ->waitFor('@stepGeneral')
                ->assertVisible('@stepGeneral');

            $this->nextSignUpStep($browser);

            // authenticated identity with organization gets the organization select step
            $browser
                ->waitFor('@stepSelectOrganization')
                ->waitFor('@organizationItem0')
                ->assertSeeIn('@organizationItem0', $organization->name)
                ->assertMissing('@organizationItem1');

            // open add organization form and go back to the select step
            $browser
                ->waitFor('@addOrganizationBtn')
                ->click('@addOrganizationBtn')
                ->waitFor('@stepOrganizationAdd');

            $this->previousSignUpStep($browser);

            $browser
                ->waitFor('@stepSelectOrganization')
                ->waitFor('@organizationItem0')
                ->click('@organizationItem0');

            $this->finishSignUp($browser);
            $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity);

            // no new organization was created
            $this->assertEquals(1, Organization::where('identity_address', $identity->address)->count());

            $this->logout($browser);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorSignupOrganizationValidation(): void
    {
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        $this->browse(function (Browser $browser) use ($startTime, $implementation) {
            $browser->visit($implementation->urlSponsorDashboard('aanmelden'));
            $email = $this->makeUniqueEmail('sponsor');

            $this->signUpWithEmail($browser, $email, $startTime);
            $this->goToOrganizationAdd($browser);

            // submit empty form
            $this->nextSignUpStep($browser);

            $browser
                ->waitFor('@nameError1')
                ->assertVisible('@ibanError1')
                ->assertVisible('@emailError1')
                ->assertVisible('@phoneError1')
                ->assertVisible('@kvkError1');

            $this->assertSignUpOrganizationFormValidation($browser);

            // nothing was created and user stays on the organization step
            $browser->assertVisible('@stepOrganizationAdd')->assertMissing('@stepFinished');

            $identity = Identity::findByEmail($email);
            $this->assertEquals(0, Organization::where('identity_address', $identity->address)->count());
        });
    }

    /**
     * @param Browser $browser
     * @param string $email
     * @param Carbon $startTime
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function signUpWithEmail(Browser $browser, string $email, Carbon $startTime): void
    {
        $browser
            ->waitFor('@stepGeneral')
            ->assertVisible('@stepGeneral');

        $this->nextSignUpStep($browser);

        $browser
            ->waitFor('@stepCreateProfile')
            ->assertVisible('@stepCreateProfile');

        $this->submitSignUpEmailForm($browser, $email, $startTime);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToOrganizationAdd(Browser $browser): void
    {
        $browser->waitUsing(10, 100, function () use ($browser) {
            if ($browser->element('@stepOrganizationAdd')) {
                return true;
            }

            if ($browser->element('@stepGeneral') && $browser->element('@nextBtn')) {
                $browser->click('@nextBtn');
            }

            return false;
        }, 'Timeout waiting for organization add step.');

        $browser->assertVisible('@stepOrganizationAdd');
    }
}
