<?php

namespace Tests\Browser;

use App\Mail\User\EmailActivationMail;
use App\Models\DemoTransaction;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Tag;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ProviderSignUpTest extends DuskTestCase
{
    use MakesTestFunds;
    use AssertsSentEmails;
    use HasFrontendActions;
    use MakesTestIdentities;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderSignupEmailFlow(): void
    {
        Cache::clear();
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        // create organization, fund and tag for apply to
        $organization = $this->prepareOrganizationAndFundApplyTo();

        $this->browse(function (Browser $browser) use ($startTime, $implementation, $organization) {
            $browser->visit($implementation->urlProviderDashboard('aanmelden'));
            $this->cleanBrowser($browser);
            $email = $this->makeUniqueEmail('provider');

            /*
             * GENERAL, CREATE PROFILE
             */
            $this->signUpWithEmail($browser, $email, $startTime);

            /*
             * ORGANIZATION CREATE
             */
            $this->addOrganization($browser);
            $this->next($browser);

            /*
             * OFFICES
             */
            $this->addOffice($browser);
            $this->next($browser);

            /*
             * EMPLOYEES
             */
            $this->addEmployee($browser);
            $this->next($browser);

            /*
             * FUND APPLICATIONS
             */
            $this->applyToFunds($browser, $organization);
            $this->next($browser);

            /*
             * PROCESS NOTICE
             */
            $browser
                ->waitFor('@stepProcessNotice')
                ->assertVisible('@stepProcessNotice')
                ->waitFor('@finishBtn')
                ->click('@finishBtn');

            $identity = Identity::findByEmail($email);
            $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
            $this->logout($browser);

            // assert fund provider created
            $fundProvider = FundProvider::query()
                ->where('fund_id', $organization->funds[0]->id)
                ->where('organization_id', $identity->organizations[0]->id)
                ->first();

            $this->assertNotNull($fundProvider);
            $this->assertEquals($fundProvider::STATE_PENDING, $fundProvider->state);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderSignupPhoneFlow(): void
    {
        Cache::clear();
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        $this->browse(function (Browser $browser) use ($startTime, $implementation) {
            $browser->visit($implementation->urlProviderDashboard('aanmelden'));
            $this->cleanBrowser($browser);

            /*
             * GENERAL
             */
            $browser
                ->waitFor('@stepGeneral')
                ->assertVisible('@stepGeneral');

            $this->next($browser);

            /*
             * INFO ME APP
             */
            $browser
                ->waitFor('@stepInfoMeApp')
                ->assertVisible('@stepInfoMeApp');

            $this->next($browser);

            /*
             * CREATE PROFILE
             */
            $browser
                ->waitFor('@stepCreateProfile')
                ->assertVisible('@stepCreateProfile');

            $browser
                ->waitFor('@deviceOptionSelect')
                ->select('@deviceOptionSelect', 'android-phone')
                ->waitFor('@phoneInput');

            $this->fillInput($browser, '@phoneInput', '0634444444');

            $browser
                ->waitFor('@phoneFormSubmitBtn')
                ->click('@phoneFormSubmitBtn');

            $browser->waitFor('@smsSent');

            $identityProxy = null;

            $browser->waitUsing(
                null,
                100,
                function () use ($startTime, &$identityProxy) {
                    $identityProxy = IdentityProxy::where('created_at', '>=', $startTime)->first();
                    $this->assertNotNull($identityProxy);

                    return true;
                },
                'Timeout waiting for proxy'
            );

            $identity = $this->makeIdentity($this->makeUniqueEmail());
            $headers = $this->makeApiHeaders($identity);

            $this->post('/api/v1/identity/proxy/authorize/token', [
                'auth_token' => $identityProxy->exchange_token,
            ], $headers)->assertSuccessful();

            /*
             * ORGANIZATION CREATE
             */
            $this->addOrganization($browser);
            $this->next($browser);

            /*
             * OFFICES
             */
            $this->addOffice($browser);
            $this->next($browser);

            /*
             * EMPLOYEES
             */
            $this->addEmployee($browser);
            $this->next($browser);

            /*
             * FUND APPLICATIONS
             */
            $this->applyToFunds($browser);
            $this->next($browser);

            /*
             * PROCESS NOTICE
             */
            $browser
                ->waitFor('@stepProcessNotice')
                ->assertVisible('@stepProcessNotice');

            $this->next($browser);

            /*
             * DEMO TRANSACTION
             */
            $browser
                ->waitFor('@stepDemoTransaction')
                ->assertVisible('@stepDemoTransaction');

            $demoTransaction = null;

            $browser->waitUsing(
                null,
                100,
                function () use ($startTime, &$demoTransaction) {
                    $demoTransaction = DemoTransaction::where('created_at', '>=', $startTime)->first();
                    $this->assertNotNull($demoTransaction);

                    return true;
                },
                'Timeout waiting for demo transaction'
            );

            $demoTransaction->update([
                'state' => $demoTransaction::STATE_ACCEPTED,
            ]);

            /*
             * FINISH
             */
            $browser
                ->waitFor('@stepFinished')
                ->assertVisible('@stepFinished')
                ->waitFor('@finishBtn')
                ->click('@finishBtn');

            $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
            $this->logout($browser);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSeveralOrganizationsAdd(): void
    {
        Cache::clear();
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        $this->browse(function (Browser $browser) use ($startTime, $implementation) {
            $browser->visit($implementation->urlProviderDashboard('aanmelden'));
            $this->cleanBrowser($browser);
            $email = $this->makeUniqueEmail('provider');

            $this->signUpWithEmail($browser, $email, $startTime);

            // create first organization
            $this->addOrganization($browser);
            $this->next($browser);

            // assert visible step OFFICES
            $browser
                ->waitFor('@stepOffices')
                ->assertVisible('@stepOffices');

            $this->previous($browser);

            // as we already have organization previous step must be organization select step
            $browser
                ->waitFor('@stepSelectOrganization')
                ->assertVisible('@stepSelectOrganization');

            // assert created organization exists and selectable
            $browser
                ->waitFor('@organizationItem0')
                ->click('@organizationItem0');

            // assert after selecting organization next step is OFFICES
            $browser
                ->waitFor('@stepOffices')
                ->assertVisible('@stepOffices');

            $this->previous($browser);

            // go to previous step and add new organization
            $browser
                ->waitFor('@stepSelectOrganization')
                ->assertVisible('@stepSelectOrganization')
                ->waitFor('@addOrganizationBtn')
                ->click('@addOrganizationBtn');

            $this->addOrganization($browser);
            $this->next($browser);

            $browser
                ->waitFor('@stepOffices')
                ->assertVisible('@stepOffices');

            $this->previous($browser);

            // go to previous step and accept that two created organization are visible
            $browser
                ->waitFor('@stepSelectOrganization')
                ->assertVisible('@stepSelectOrganization');

            $browser
                ->waitFor('@organizationItem0')
                ->assertVisible('@organizationItem0')
                ->waitFor('@organizationItem1')
                ->assertVisible('@organizationItem1');

            $browser->refresh();
            $this->assertIdentityAuthenticatedOnProviderDashboard($browser, Identity::findByEmail($email));
            $this->logout($browser);

            // assert after logout login page is visible
            $browser
                ->waitFor('@loginPage')
                ->assertVisible('@loginPage');
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrganizationValidation(): void
    {
        Cache::clear();
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        $this->browse(function (Browser $browser) use ($startTime, $implementation) {
            $browser->visit($implementation->urlProviderDashboard('aanmelden'));
            $this->cleanBrowser($browser);
            $email = $this->makeUniqueEmail('provider');

            $this->signUpWithEmail($browser, $email, $startTime);

            $this->addOrganizationWithValidation($browser);
            $browser->script('localStorage.clear();');
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
        /*
         * GENERAL
         */
        $browser
            ->waitFor('@stepGeneral')
            ->assertVisible('@stepGeneral');

        $this->next($browser);

        /*
         * INFO ME APP
         */
        $browser
            ->waitFor('@stepInfoMeApp')
            ->assertVisible('@stepInfoMeApp');

        $this->next($browser);

        /*
         * CREATE PROFILE
         */
        $browser
            ->waitFor('@stepCreateProfile')
            ->assertVisible('@stepCreateProfile')
            ->waitFor('@signupByEmail')
            ->click('@signupByEmail');

        /*
         * EMAIL FORM
         */
        $browser
            ->waitFor('@emailForm')
            ->assertVisible('@emailForm');

        $this->fillInput($browser, '@emailInput', $email);

        $browser
            ->waitFor('@emailFormSubmit')
            ->click('@emailFormSubmit')
            ->waitFor('@authEmailSent')
            ->assertVisible('@authEmailSent');

        $this->assertMailableSent($email, EmailActivationMail::class, $startTime);
        $this->assertEmailConfirmationLinkSent($email, $startTime);

        $browser->visit($this->findFirstEmailConfirmationLink($email, $startTime));
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     * @return void
     */
    protected function addOrganization(Browser $browser): void
    {
        $browser
            ->waitFor('@stepOrganizationAdd')
            ->assertVisible('@stepOrganizationAdd');

        $this->fillInput($browser, '@nameInput', 'Test Organization');

        $iban = $this->makeIban();

        $this
            ->fillInput($browser, '@ibanInput', $iban)
            ->fillInput($browser, '@ibanConfirmationInput', $iban);

        $this
            ->fillInput($browser, '@emailInput', $this->makeUniqueEmail())
            ->fillInput($browser, '@phoneInput', '31612345678')
            ->fillInput($browser, '@websiteInput', 'https://example.com', true);

        $browser
            ->waitFor('@emailPublicCheckbox')->click('@emailPublicCheckbox')
            ->waitFor('@phonePublicCheckbox')->click('@phonePublicCheckbox')
            ->waitFor('@websitePublicCheckbox')->click('@websitePublicCheckbox');

        $this->changeSelectControl($browser, '@businessTypeSelect', index: 0);

        $this
            ->fillInput($browser, '@kvkInput', '00000000')
            ->fillInput($browser, '@btwInput', 'NL123456789B01');
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     * @return void
     */
    protected function addOrganizationWithValidation(Browser $browser): void
    {
        // first fill with valid data
        $this->addOrganization($browser);

        $this->fillInput($browser, '@nameInput', '', true);

        $this->next($browser);
        $this->assertValidationErrors($browser, 1);

        $invalidIban = 'invalid_iban';

        $this
            ->fillInput($browser, '@ibanInput', $invalidIban, true)
            ->fillInput($browser, '@ibanConfirmationInput', $invalidIban, true);

        $this->next($browser);
        $this->assertValidationErrors($browser, 2);

        $this->fillInput($browser, '@emailInput', 'invalid@email', true);

        $this->next($browser);
        $this->assertValidationErrors($browser, 3);

        $this->fillInput($browser, '@phoneInput', 'invalid_phone', true);

        $this->next($browser);
        $this->assertValidationErrors($browser, 4);

        $this->fillInput($browser, '@websiteInput', 'invalid_website', true);

        $this->next($browser);
        $this->assertValidationErrors($browser, 5);

        $this->fillInput($browser, '@kvkInput', 'invalid_kvk', true);

        $this->next($browser);
        $this->assertValidationErrors($browser, 7); // kvk can throw 2 errors
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     * @return void
     */
    protected function addOffice(Browser $browser): void
    {
        $browser
            ->waitFor('@stepOffices')
            ->assertVisible('@stepOffices');

        $data = [
            'address' => 'Dam 1, Amsterdam',
            'phone' => '+31612345678',
        ];

        $this->fillOfficeForm($browser, $data);

        $browser->within('@office0', function (Browser $browser) {
            $browser->waitFor('@editOffice')->click('@editOffice');
        });

        $browser
            ->waitFor('@officeEditForm')
            ->assertVisible('@officeEditForm');

        $browser
            ->waitFor('@addressInput')
            ->assertInputValue('@addressInput', Arr::get($data, 'address'))
            ->waitFor('@phoneInput')
            ->assertInputValue('@phoneInput', Arr::get($data, 'phone'));

        $browser
            ->waitFor('@cancelAddressBtn')
            ->click('@cancelAddressBtn')
            ->waitFor('@office0');

        $browser->within('@office0', function (Browser $browser) {
            $browser->assertMissing('@deleteOffice');
        });

        $browser
            ->waitFor('@addOfficeBtn')
            ->click('@addOfficeBtn');

        $this->fillOfficeForm($browser, [
            'address' => 'Sam 5, Amsterdam',
            'phone' => '+31612345678',
        ]);

        $browser->waitFor('@office1');

        $browser->within('@office1', function (Browser $browser) {
            $browser->waitFor('@deleteOffice')->click('@deleteOffice');
        });

        $browser->waitFor('@modalDangerZone');
        $browser->waitFor('@btnDangerZoneSubmit');
        $browser->press('@btnDangerZoneSubmit');
        $browser->waitUntilMissing('@modalDangerZone');

        $browser->waitUntilMissing('@office1');

        // assert with db
        $office = Office::with('schedules')->latest()->first();

        $this->assertSame(Arr::get($data, 'address'), $office->address);
        $this->assertSame(Arr::get($data, 'phone'), $office->phone);

        $this->assertEquals(3, $office->schedules->count());

        foreach ($office->schedules as $schedule) {
            $this->assertSame('10:00:00', $schedule->start_time);
            $this->assertSame('18:00:00', $schedule->end_time);
            $this->assertSame('12:00:00', $schedule->break_start_time);
            $this->assertSame('14:00:00', $schedule->break_end_time);
        }
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     * @return void
     */
    protected function addEmployee(Browser $browser): void
    {
        $employeeEmail = $this->makeUniqueEmail('employee');

        $browser
            ->waitFor('@stepEmployees')
            ->assertVisible('@stepEmployees');

        $this->fillInput($browser, '@emailInput', $employeeEmail);

        $browser
            ->waitFor('@submitEmployeeForm')
            ->click('@submitEmployeeForm');

        $browser
            ->waitFor('@modalNotification')
            ->assertVisible('@modalNotification')
            ->waitFor('@submitBtn')
            ->click('@submitBtn')
            ->waitUntilMissing('@modalNotification');

        $browser->waitForTextIn('@stepEmployees', $employeeEmail);

        $browser
            ->waitFor('@deleteEmployeeBtn0')
            ->click('@deleteEmployeeBtn0');

        $browser->waitUntilMissingText($employeeEmail);
    }

    /**
     * @param Browser $browser
     * @param Organization|null $organization
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function applyToFunds(Browser $browser, ?Organization $organization = null): void
    {
        $browser
            ->waitFor('@stepFundApply')
            ->assertVisible('@stepFundApply');

        $browser
            ->waitFor('[data-dusk^="fundRow"]')
            ->waitFor('@organizationFilterSelect')
            ->assertVisible('@organizationFilterSelect')
            ->waitFor('@tagFilterSelect')
            ->assertVisible('@tagFilterSelect');

        $browser
            ->waitFor('@skipFundApplicationsCheckbox')
            ->click('@skipFundApplicationsCheckbox');

        $browser
            ->waitFor('@nextBtn')
            ->assertVisible('@nextBtn');

        if ($organization) {
            /** @var Fund $fund */
            $fund = $organization->funds->first();
            $tag = $fund->tags()->first();

            $browser->click('@skipFundApplicationsCheckbox');

            $browser->select('@organizationFilterSelect', $organization->id);
            $browser->select('@tagFilterSelect', $tag->key);

            $browser->waitForTextIn('@totalCount', '1');

            $browser->waitUsing(
                null,
                100,
                function () use ($browser) {
                    return count($browser->elements('[data-dusk^="fundRow"]')) === 1;
                },
                'No fund checkbox found'
            );

            $browser
                ->waitFor('@selectAllFundsBtn')
                ->click('@selectAllFundsBtn');

            $browser
                ->waitFor('@applyFundsBtn')
                ->click('@applyFundsBtn');

            $browser
                ->waitFor('@nextBtn')
                ->assertVisible('@nextBtn');
        }
    }

    /**
     * @param Browser $browser
     * @return void
     */
    protected function cleanBrowser(Browser $browser): void
    {
        $browser->driver->manage()->deleteAllCookies();

        $browser->script('
            localStorage.clear();
            sessionStorage.clear();
        ');
    }

    /**
     * @param Browser $browser
     * @param array $data
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function fillOfficeForm(Browser $browser, array $data): void
    {
        $browser
            ->waitFor('@officeEditForm')
            ->assertVisible('@officeEditForm');

        $browser
            ->waitFor('@saveAddressBtn')
            ->click('@saveAddressBtn');

        $this->assertValidationErrors($browser, 1);

        $this
            ->fillInput($browser, '@addressInput', Arr::get($data, 'address'))
            ->fillInput($browser, '@phoneInput', Arr::get($data, 'phone'));

        $browser
            ->waitFor('@officeSameHours')
            ->click('@officeSameHours')
            ->waitFor('@weekendSameHours')
            ->click('@weekendSameHours');

        foreach (range(0, 2) as $index) {
            $browser->waitFor("@weekDay$index");

            $browser->within("@weekDay$index", function (Browser $browser) {
                $browser
                    ->waitFor('@weekDayStartTime')
                    ->select('@weekDayStartTime', '10:00');

                $browser
                    ->waitFor('@weekDayEndTime')
                    ->select('@weekDayEndTime', '18:00');

                $browser
                    ->waitFor('@weekDayBreakStartTime')
                    ->select('@weekDayBreakStartTime', '12:00');

                $browser
                    ->waitFor('@weekDayBreakEndTime')
                    ->select('@weekDayBreakEndTime', '14:00');
            });
        }

        foreach (range(3, 4) as $index) {
            $browser->waitFor("@weekDay$index");

            $browser->within("@weekDay$index", function (Browser $browser) {
                $browser->click('@weekDayClosedCheckbox');
            });
        }

        $browser
            ->waitFor('@saveAddressBtn')
            ->click('@saveAddressBtn')
            ->waitFor('@office0');
    }

    /**
     * @param Browser $browser
     * @param int $count
     * @throws TimeoutException
     * @return void
     */
    private function assertValidationErrors(Browser $browser, int $count): void
    {
        $browser->waitUsing(
            null,
            100,
            fn () => count($browser->elements('.form-error')) === $count,
            "Timeout waiting for $count validation errors."
        );
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string|int $value
     * @param bool $clear
     * @throws TimeoutException
     * @return $this
     */
    private function fillInput(
        Browser $browser,
        string $selector,
        string|int $value,
        bool $clear = false
    ): static {
        $browser->waitFor($selector);

        if ($clear) {
            $this->clearField($browser, $selector);
        }

        $browser->type($selector, $value);

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     * @return void
     */
    private function next(Browser $browser): void
    {
        $browser->waitFor('@nextBtn')->click('@nextBtn');
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     * @return void
     */
    private function previous(Browser $browser): void
    {
        $browser->waitFor('@previousBtn')->click('@previousBtn');
    }

    /**
     * @return Organization
     */
    private function prepareOrganizationAndFundApplyTo(): Organization
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fundTag = $this->faker->name();

        $fund = $this->makeTestFund(organization: $organization, fundConfigsData: [
            'allow_provider_sign_up' => true,
        ]);

        /** @var Tag $tag */
        $tag = $fund->tags()->firstOrCreate([
            'key' => Str::slug($fundTag),
            'scope' => 'provider',
        ]);

        $tag->translateOrNew(app()->getLocale())->fill([
            'name' => $fundTag,
        ])->save();

        return $organization;
    }
}
