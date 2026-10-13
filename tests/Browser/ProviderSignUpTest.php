<?php

namespace Tests\Browser;

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
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\HasSignUpActions;
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
    public function testProviderSignupEmailFlow(): void
    {
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        // create organization, fund and tag for apply to
        $organization = $this->prepareOrganizationAndFundApplyTo();

        $this->browse(function (Browser $browser) use ($startTime, $implementation, $organization) {
            $browser->visit($implementation->urlProviderDashboard('aanmelden'));
            $email = $this->makeUniqueEmail('provider');

            $this->signUpWithEmail($browser, $email, $startTime);

            $this->fillSignUpOrganizationForm($browser);
            $this->nextSignUpStep($browser);

            $this->addOffice($browser);
            $this->nextSignUpStep($browser);

            $this->addEmployee($browser);
            $this->nextSignUpStep($browser);

            $this->applyToFunds($browser, $organization);
            $this->nextSignUpStep($browser);

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
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        $this->browse(function (Browser $browser) use ($startTime, $implementation) {
            $browser->visit($implementation->urlProviderDashboard('aanmelden'));

            /*
             * GENERAL
             */
            $browser
                ->waitFor('@stepGeneral')
                ->assertVisible('@stepGeneral');

            $this->nextSignUpStep($browser);

            /*
             * INFO ME APP
             */
            $browser
                ->waitFor('@stepInfoMeApp')
                ->assertVisible('@stepInfoMeApp');

            $this->nextSignUpStep($browser);

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

            $this->fillSignUpInput($browser, '@phoneInput', '0634444444');

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
            $this->fillSignUpOrganizationForm($browser);
            $this->nextSignUpStep($browser);

            /*
             * OFFICES
             */
            $this->addOffice($browser);
            $this->nextSignUpStep($browser);

            /*
             * EMPLOYEES
             */
            $this->addEmployee($browser);
            $this->nextSignUpStep($browser);

            /*
             * FUND APPLICATIONS
             */
            $this->applyToFunds($browser);
            $this->nextSignUpStep($browser);

            /*
             * PROCESS NOTICE
             */
            $browser
                ->waitFor('@stepProcessNotice')
                ->assertVisible('@stepProcessNotice');

            $this->nextSignUpStep($browser);

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

            $this->finishSignUp($browser);

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
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        $this->browse(function (Browser $browser) use ($startTime, $implementation) {
            $browser->visit($implementation->urlProviderDashboard('aanmelden'));
            $email = $this->makeUniqueEmail('provider');

            $this->signUpWithEmail($browser, $email, $startTime);

            // create first organization
            $this->fillSignUpOrganizationForm($browser);
            $this->nextSignUpStep($browser);

            // assert visible step OFFICES
            $browser
                ->waitFor('@stepOffices')
                ->assertVisible('@stepOffices');

            $this->previousSignUpStep($browser);

            // as we already have organization previous step must be organization select step
            $browser
                ->waitFor('@stepSelectOrganization')
                ->assertVisible('@stepSelectOrganization');

            // assert created organization exists and selectable
            $browser
                ->waitFor('@organizationItem0')
                ->assertVisible('@organizationItem0')
                ->assertMissing('@organizationItem1')
                ->click('@organizationItem0');

            // assert after selecting organization next step is OFFICES
            $browser
                ->waitFor('@stepOffices')
                ->assertVisible('@stepOffices');

            $this->previousSignUpStep($browser);

            // go to previous step and add new organization
            $browser
                ->waitFor('@stepSelectOrganization')
                ->assertVisible('@stepSelectOrganization')
                ->waitFor('@addOrganizationBtn')
                ->click('@addOrganizationBtn');

            $this->fillSignUpOrganizationForm($browser);
            $this->nextSignUpStep($browser);

            $browser
                ->waitFor('@stepOffices')
                ->assertVisible('@stepOffices');

            $this->previousSignUpStep($browser);

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
        $startTime = Carbon::now();
        $implementation = Implementation::where('key', 'nijmegen')->first();

        $this->browse(function (Browser $browser) use ($startTime, $implementation) {
            $browser->visit($implementation->urlProviderDashboard('aanmelden'));
            $email = $this->makeUniqueEmail('provider');

            $this->signUpWithEmail($browser, $email, $startTime);
            $this->assertSignUpOrganizationFormValidation($browser);
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
            ->waitFor('@stepInfoMeApp')
            ->assertVisible('@stepInfoMeApp');

        $this->nextSignUpStep($browser);

        $browser
            ->waitFor('@stepCreateProfile')
            ->assertVisible('@stepCreateProfile')
            ->waitFor('@signupByEmail')
            ->click('@signupByEmail');

        $this->submitSignUpEmailForm($browser, $email, $startTime);
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
            'phone' => '+31 6 12345678',
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

        $this->fillSignUpInput($browser, '@phoneInput', 'invalid_phone', true);
        $browser->click('@saveAddressBtn');
        $browser->waitFor('@phoneError1')->assertVisible('@phoneError1');

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

        $data = [
            'address' => 'Sam 5, Amsterdam',
            'phone' => '+31612345678',
        ];

        $this->fillOfficeForm($browser, $data);

        $browser->waitFor('@office1');

        $browser->within('@office0', function (Browser $browser) {
            $browser->waitFor('@deleteOffice')->click('@deleteOffice');
        });

        $browser->waitFor('@modalDangerZone');
        $browser->waitFor('@btnDangerZoneSubmit');
        $browser->press('@btnDangerZoneSubmit');
        $browser->waitUntilMissing('@modalDangerZone');

        $browser->waitUntilMissing('@office1');

        $browser->within('@office0', function (Browser $browser) use ($data) {
            $browser->assertSee(Arr::get($data, 'address'))->assertMissing('@deleteOffice');
        });

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

        $this->fillSignUpInput($browser, '@emailInput', $employeeEmail);

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

            $browser->waitUsing(
                null,
                100,
                fn () => trim($browser->text('@totalCount')) === '1',
                'Expected one filtered fund.'
            );

            $browser->waitFor("@fundRow{$fund->id}");

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

        $browser->waitFor('@addressError1')->assertVisible('@addressError1');

        $this->fillSignUpInput($browser, '@phoneInput', 'invalid_phone', true);
        $browser->click('@saveAddressBtn');
        $browser->waitFor('@phoneError1')->assertVisible('@phoneError1');
        $browser->assertVisible('@addressError1');

        $this
            ->fillSignUpInput($browser, '@addressInput', Arr::get($data, 'address'))
            ->fillSignUpInput($browser, '@phoneInput', Arr::get($data, 'phone'), true);

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
