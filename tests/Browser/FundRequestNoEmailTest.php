<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\RecordType;
use App\Models\Role;
use App\Models\Voucher;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\HasFundRequestFormActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestNoEmailTest extends DuskTestCase
{
    use AssertsSentEmails;
    use WithFaker;
    use MakesTestFunds;
    use HasFundRequestFormActions;
    use HasFrontendActions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     */
    public function testFundRequestNoEmailOptionalAgeCriteria(): void
    {
        [$implementation, $organization] = $this->getImplementationAndOrganization();
        [$fund, $recordType] = $this->makeFundWithAgeCriteria($organization, [
            'email_required' => false,
            'contact_info_enabled' => false,
            'contact_info_required' => false,
        ]);

        $this->runNoEmailFundRequestFlow($implementation, $fund, [
            'age' => 19,
            'contact_info' => null,
            'privacy_terms' => false,
        ]);

        $this->deleteFund($fund);
        $recordType->delete();
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestNoEmailWithPrivacyTermsAndContactInfo(): void
    {
        [$implementation, $organization] = $this->getImplementationAndOrganization();
        [$fund, $recordType] = $this->makeFundWithAgeCriteria($organization, [
            'email_required' => false,
            'contact_info_enabled' => true,
            'contact_info_required' => true,
        ]);

        $this->runNoEmailFundRequestFlow($implementation, $fund, [
            'age' => 19,
            'contact_info' => 'Lorem ipsum',
            'privacy_terms' => true,
        ]);

        $this->deleteFund($fund);
        $recordType->delete();
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestNoEmailAutoValidationWithoutCriteria(): void
    {
        [$implementation, $organization] = $this->getImplementationAndOrganization();

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'email_required' => false,
            'contact_info_enabled' => false,
            'contact_info_required' => false,
        ]);

        $rolesValidator = Role::where('key', 'validation')->pluck('id')->toArray();
        $employee = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $rolesValidator);

        $fund->forceFill([
            'default_validator_employee_id' => $employee->id,
            'auto_requests_validation' => true,
        ])->save();

        $this->runNoEmailFundRequestFlow($implementation, $fund, [
            'contact_info' => null,
            'privacy_terms' => false,
            'auto_validation' => true,
        ]);

        $this->deleteFund($fund);
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestNoEmailAutoValidationWithoutCriteriaBus2020(): void
    {
        [$implementation, $organization] = $this->getImplementationAndOrganization();

        [$fund, $recordType] = $this->makeFundWithAgeCriteria($organization, [
            'key' => 'bus_2020',
            'email_required' => false,
            'contact_info_enabled' => false,
            'contact_info_required' => false,
        ]);

        $rolesValidator = Role::where('key', 'validation')->pluck('id')->toArray();
        $employee = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $rolesValidator);

        $fund->forceFill([
            'default_validator_employee_id' => $employee->id,
            'auto_requests_validation' => true,
        ])->save();

        $this->runNoEmailFundRequestFlow($implementation, $fund, [
            'contact_info' => null,
            'privacy_terms' => false,
            'auto_validation' => true,
            'custom_criteria_confirmation' => true,
        ]);

        $this->deleteFund($fund);
        $recordType->delete();
    }

    /**
     * @throws Throwable
     */
    public function testFundRequestNoEmailWithEmailVerification(): void
    {
        [$implementation, $organization] = $this->getImplementationAndOrganization();
        [$fund, $recordType] = $this->makeFundWithAgeCriteria($organization, [
            'email_required' => false,
            'contact_info_enabled' => false,
            'contact_info_required' => false,
        ]);

        $this->runEmailVerificationFundRequestFlow($implementation, $fund, [
            'age' => 19,
            'contact_info' => null,
            'privacy_terms' => false,
            'email' => $this->makeUniqueEmail(),
        ]);

        $this->deleteFund($fund);
        $recordType->delete();
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param array $config
     * @throws Throwable
     * @return void
     */
    protected function runNoEmailFundRequestFlow(
        Implementation $implementation,
        Fund $fund,
        array $config,
    ): void {
        $this->withFundRequestEnvironment($implementation, $fund, $config, function () use (
            $implementation,
            $fund,
            $config,
        ) {
            $requester = $this->makeIdentity(null, '123456789');

            $this->browse(function (Browser $browser) use ($implementation, $fund, $requester, $config) {
                $customConfirmationStep = $config['custom_criteria_confirmation'] ?? false;

                $this->openFundRequestFormByDigiD($browser, $implementation, $fund, $requester, !$customConfirmationStep);
                $this->completeFundRequestForm($browser, $config, true);
                $this->logout($browser);
            });

            if ($config['auto_validation'] ?? false) {
                $this->assertAutoValidatedVoucher($fund, $requester);
            } else {
                $this->approveFundRequestAndAssertVoucher($fund, $requester);
            }
        });
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param array $config
     * @throws Throwable
     * @return void
     */
    protected function runEmailVerificationFundRequestFlow(
        Implementation $implementation,
        Fund $fund,
        array $config,
    ): void {
        $this->withFundRequestEnvironment($implementation, $fund, $config, function () use (
            $implementation,
            $fund,
            $config,
        ) {
            $requester = $this->makeIdentity(null, '123456789');
            $email = $config['email'];

            $this->browse(function (Browser $browser) use (
                $implementation,
                $fund,
                $requester,
                $config,
                $email,
            ) {
                $this->openFundRequestFormByDigiD($browser, $implementation, $fund, $requester);

                if ($config['privacy_terms']) {
                    $this->acceptPrivacyAndTerms($browser);
                }

                $startTime = now();
                $browser->within('@fundRequestEmailForm', function (Browser $browser) use ($email) {
                    $browser->type('@fundRequestEmailInput', $email);
                    $browser->click('@fundRequestEmailSubmit');
                });

                $browser->waitFor('@fundRequestEmailSent');

                $this->assertEmailVerificationLinkSent($email, $startTime);
                $browser->visit($this->findFirstEmailVerificationLink($email, $startTime));
                $this->assertAndCloseSuccessNotification($browser);

                $this->openFundRequestFormByDigiD($browser, $implementation, $fund, $requester);
                $this->completeFundRequestForm($browser, $config, false);
                $this->logout($browser);
            });

            $this->approveFundRequestAndAssertVoucher($fund, $requester);
        });
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param array $config
     * @param callable $callback
     * @throws Throwable
     * @return void
     */
    protected function withFundRequestEnvironment(
        Implementation $implementation,
        Fund $fund,
        array $config,
        callable $callback,
    ): void {
        $implementationData = $implementation->only([
            'digid_enabled', 'digid_required', 'digid_connection_type', 'digid_app_id',
            'digid_shared_secret', 'digid_a_select_server', 'show_privacy_checkbox', 'show_terms_checkbox',
        ]);

        $organization = $implementation->organization;
        $organizationData = $organization->only(['fund_request_resolve_policy']);

        $privacyPage = null;
        $termsPage = null;

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use (
            $implementation,
            $organization,
            $fund,
            $config,
            &$privacyPage,
            &$termsPage,
            $callback,
        ) {
            $implementation->forceFill([
                'digid_enabled' => true,
                'digid_required' => true,
                'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
                'digid_app_id' => 'test',
                'digid_shared_secret' => 'test',
                'digid_a_select_server' => 'test',
                'show_privacy_checkbox' => $config['privacy_terms'] ?? false,
                'show_terms_checkbox' => $config['privacy_terms'] ?? false,
            ])->save();

            if ($config['privacy_terms'] ?? false) {
                [$privacyPage, $termsPage] = $this->ensurePrivacyAndTermsImplementationPages($implementation);
            }

            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $fund->fund_formulas()->forceDelete();
            $fund->fund_formulas()->create(['type' => FundFormula::TYPE_FIXED, 'amount' => 100]);

            $callback();
        }, function () use (&$privacyPage, &$termsPage) {
            $privacyPage?->delete();
            $termsPage?->delete();
        });
    }

    /**
     * @param Browser $browser
     * @param array $config
     * @param bool $skipEmail
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function completeFundRequestForm(Browser $browser, array $config, bool $skipEmail): void
    {
        if ($config['privacy_terms'] ?? false) {
            $this->acceptPrivacyAndTerms($browser);
        }

        if ($config['custom_criteria_confirmation'] ?? false) {
            $this->acceptConfirmCriteriaIfPresent($browser);
        }

        if ($skipEmail) {
            $this->skipEmailStep($browser);
        }

        if ($browser->element('@criteriaStepsOverview')) {
            $browser->waitFor('@criteriaStepsOverview');
            $browser->waitFor('@nextStepButton')->click('@nextStepButton');
        }

        if (!empty($config['age'])) {
            $browser->waitFor('@controlNumber');
            $this->fillInput($browser, '@controlNumber', 'number', $config['age']);
            $browser->waitFor('@nextStepButton')->click('@nextStepButton');
        }

        $this->acceptConfirmCriteriaIfPresent($browser);

        if (!empty($config['contact_info'])) {
            $browser->waitFor('#fund_request_contact_info');
            $browser->type('#fund_request_contact_info', $config['contact_info']);
            $this->clickFooterAction($browser);
        }

        $this->acceptConfirmCriteriaIfPresent($browser);

        if ($config['auto_validation'] ?? false) {
            $browser->waitFor('@voucherTitle');
        } else {
            $browser->waitFor('@submitButton')->click('@submitButton');
            $browser->waitFor('@fundRequestSuccess');
        }
    }

    /**
     * @return array{Implementation, Organization}
     */
    protected function getImplementationAndOrganization(string $key = 'nijmegen'): array
    {
        $implementation = Implementation::byKey($key);
        $this->assertNotNull($implementation);
        $organization = $implementation->organization;
        $this->assertNotNull($organization);

        return [$implementation, $organization];
    }

    /**
     * @param Organization $organization
     * @param array $fundConfig
     * @return array{Fund, RecordType}
     */
    protected function makeFundWithAgeCriteria(Organization $organization, array $fundConfig): array
    {
        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], $fundConfig);

        $recordType = $this->makeCriteriaRecordType(
            $organization,
            RecordType::TYPE_NUMBER,
            RecordType::CONTROL_TYPE_NUMBER,
            'age_' . now()->timestamp,
        );

        $this->makeFundCriteria($fund, [[
            'title' => 'Age',
            'description' => 'Enter your age',
            'record_type_key' => $recordType->key,
            'operator' => '>=',
            'value' => 18,
            'show_attachment' => false,
        ]]);

        return [$fund, $recordType];
    }

    /**
     * @param Fund $fund
     * @param Identity $requester
     * @return void
     */
    protected function approveFundRequestAndAssertVoucher(Fund $fund, Identity $requester): void
    {
        $fundRequest = FundRequest::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->first();

        $this->assertNotNull($fundRequest);

        $rolesValidator = Role::where('key', 'validation')->pluck('id')->toArray();
        $employee = $fund->organization->addEmployee($this->makeIdentity($this->makeUniqueEmail()), $rolesValidator);
        $fundRequest->assignEmployee($employee)->approve();

        $voucher = Voucher::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($voucher);
        $this->assertEquals(100, (float) $voucher->amount);
    }

    /**
     * @param Fund $fund
     * @param Identity $requester
     * @return void
     */
    protected function assertAutoValidatedVoucher(Fund $fund, Identity $requester): void
    {
        $fundRequest = FundRequest::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->first();

        $this->assertNotNull($fundRequest);
        $this->assertEquals(FundRequest::STATE_APPROVED, $fundRequest->state);
        $this->assertEquals($fund->default_validator_employee_id, $fundRequest->employee_id);

        $voucher = Voucher::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($voucher);
        $this->assertEquals(100, (float) $voucher->amount);
    }
}
