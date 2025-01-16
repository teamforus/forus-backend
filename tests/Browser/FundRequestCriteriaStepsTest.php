<?php

namespace Browser;

use App\Helpers\Arr;
use App\Models\Fund;
use App\Models\FundCriteriaStep;
use App\Models\FundCriterion;
use App\Models\FundRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\RecordType;
use App\Models\Voucher;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Laravel\Dusk\Browser;
use Tests\TestCases\FundRequestCriteriaStepsTestCases;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class FundRequestCriteriaStepsTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Client-Type' => 'webshop',
    ];

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestControlTypeCase1(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$controlTypeTestCase1);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestControlTypeCase2(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$controlTypeTestCase2);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestStepCase1(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$stepTestCase1);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestStepCase2(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$stepTestCase2);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestConditionalStepCase1(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$conditionalStepTestCase1);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestConditionalStepCase2(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$conditionalStepTestCase2);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionDigid(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$applyDigidTestCase);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionDigidCase2(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$applyDigidTestCase2);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionRequestSkipped(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$applyRequestSkippedTestCase);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionRequest(): void
    {
        $this->processFundRequestTestCase(FundRequestCriteriaStepsTestCases::$applyRequestTestCase);
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionCode(): void
    {
        $this->processApplyWithCodeTestCase(FundRequestCriteriaStepsTestCases::$applyCodeTestCase);
    }

    /**
     * @param array $testCase
     * @return void
     * @throws \Throwable
     */
    protected function processFundRequestTestCase(array $testCase): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey($testCase['implementation']['key']);

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        // store previous model attributes to reset it after test
        $recordTypes = RecordType::all()->toArray();
        $this->configureRecordTypes($testCase['record_types']);

        $implementationData = $implementation->only(array_keys($testCase['implementation']));
        $implementation->forceFill($testCase['implementation'])->save();

        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $fund = $this->createFundAndConfigure($implementation->organization, $testCase);

        if ($testCase['apply_option'] === 'digid') {
            $requester->setBsnRecord('12345678');
        }

        $this->browse(function (Browser $browser) use (
            $implementation, $fund, $requester, $testCase
        ) {
            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and assert request button available
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            // assert request button available
            $browser->waitFor('@requestButton')->click('@requestButton');

            // check available options and select needed
            if (!$testCase['skip_apply_option_select']) {
                foreach ($testCase['available_apply_options'] as $applyOption) {
                    $selector = $this->getApplyOptionSelector($applyOption);
                    $selector && $browser->waitFor($selector);
                }

                $selector = $this->getApplyOptionSelector($testCase['apply_option']);
                $selector && $browser->waitFor($selector)->click($selector);
            }

            $browser
                ->waitFor('@criteriaStepsOverview')
                ->within('@criteriaStepsOverview', function (Browser $b) use ($testCase) {
                    array_walk($testCase['assert_overview_titles'], fn($title) => $b->assertSee($title));
                });

            $browser->waitFor('@nextStepButton')->click('@nextStepButton');

            $this->fillRequestForm($browser, $testCase);

            // Logout user
            $this->logout($browser);
        });

        $request = FundRequest::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->exists();

        $this->assertTrue($request);

        // delete created fund
        $fund->criteria()
            ->get()
            ->each(fn (FundCriterion $criteria) => $criteria->fund_criterion_rules()->delete());

        $fund->criteria()->delete();
        $fund->criteria_steps()->delete();
        $fund->delete();

        // reset models to previous attributes
        array_walk($recordTypes, function ($type) {
            RecordType::where('id', $type['id'])->update(Arr::only($type, [
                'key', 'type', 'system', 'criteria', 'vouchers', 'organization_id', 'control_type',
            ]));
        });

        $implementation->forceFill($implementationData)->save();
    }

    /**
     * @param Browser $browser
     * @param array $testCase
     * @return void
     * @throws TimeoutException
     */
    protected function fillRequestForm(Browser $browser, array $testCase): void
    {
        $browser->waitFor('@fundRequestForm');

        $browser->within('@fundRequestForm', function (Browser $browser) use ($testCase) {
            foreach ($testCase['steps_data'] as $step) {
                $browser->waitForTextIn('.sign_up-pane-header', $step['title']);

                foreach ($step['fields'] as $field) {
                    $field['title'] && $browser->waitForText($field['title']);
                    $field['description'] && $browser->waitForText($field['description']);

                    switch ($field['type']) {
                        case 'select':
                            $browser->waitFor('@selectControl')->click('@selectControl');
                            $this->findOptionElement($browser, $field['value'])->click();
                            break;
                        case 'text':
                            $browser->waitFor('@controlText');
                            $browser->type('@controlText', $field['value']);
                            break;
                        case 'number':
                            $browser->waitFor('@controlNumber');
                            $browser->type('@controlNumber', $field['value']);
                            break;
                        case 'currency':
                            $browser->waitFor('@controlCurrency');
                            $browser->type('@controlCurrency', $field['value']);
                            break;
                        case 'checkbox':
                            $browser->waitFor('@controlCheckbox')->click('@controlCheckbox');
                            break;
                        case 'step':
                            $browser->waitFor('@controlStep');
                            $browser->within('@controlStep', function (Browser $browser) use ($field) {
                                for ($i = 0; $i < $field['value']; $i++) {
                                    $browser->click('@increaseStep');
                                }
                            });
                            break;
                    }
                }

                // go to last criteria values screen
                $browser->click('@nextStepButton');
            }

            // submit fund request form
            $browser->waitFor('@submitButton')->click('@submitButton');
            $browser->waitFor('@fundRequestSuccess');
        });
    }

    /**
     * @param array $testCase
     * @return void
     * @throws \Throwable
     */
    protected function processApplyWithCodeTestCase(array $testCase): void
    {
        $now = now();

        // Configure implementation and fund
        $implementation = Implementation::byKey($testCase['implementation']['key']);
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        $implementationData = $implementation->only(array_keys($testCase['implementation']));
        $implementation->forceFill($testCase['implementation'])->save();

        $requester = $this->makeIdentity($this->makeUniqueEmail());

        $fund = $this->makeTestFund(
            $implementation->organization,
            $testCase['fund'],
            $testCase['fund_config']
        );

        $this->addTestCriteriaToFund($fund);
        $prevalidation = $this->makePrevalidationForTestCriteria($implementation->organization, $fund);

        $this->browse(function (Browser $browser) use (
            $implementation, $fund, $requester, $prevalidation
        ) {
            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and assert request button available
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            // assert request button available
            $browser->waitFor('@requestButton')->click('@requestButton');

            // select code option
            $browser->waitFor('@codeOption')->click('@codeOption');

            // fill code
            $code = str_replace('-', '', $prevalidation->uid);

            $browser
                ->waitFor('.block-pincode')
                ->within('.block-pincode', function (Browser $browser) use ($code) {
                    $elements = $browser->elements('input.pincode-number');
                    array_walk($elements, function (RemoteWebElement $element, $index) use ($code) {
                        $element->sendKeys($code[$index] ?? '');
                    });
                });

            $browser->waitUntilEnabled('@codeFormSubmit')->click('@codeFormSubmit');

            // assert requester got voucher
            $browser->waitFor('@voucherTitle');
            $browser->assertSeeIn('@voucherTitle', $fund->name);

            // Logout user
            $this->logout($browser);
        });

        $this->assertEquals(Prevalidation::STATE_USED, $prevalidation->refresh()->state);

        $voucher = Voucher::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->exists();

        $this->assertTrue($voucher);

        // delete created fund
        $fund->criteria()->delete();
        $fund->criteria_steps()->delete();
        $fund->delete();

        $implementation->forceFill($implementationData)->save();
        RecordType::where('created_at', '>=', $now)->delete();
    }


    /**
     * @param string $option
     * @return string|null
     */
    protected function getApplyOptionSelector(string $option): ?string
    {
        return match ($option) {
            'code' => '@codeOption',
            'digid' => '@digidOption',
            'request' => '@requestOption',
            default => null,
        };
    }

    /**
     * @param Browser $browser
     * @param string $title
     * @return RemoteWebElement|null
     * @throws TimeoutException
     */
    protected function findOptionElement(Browser $browser, string $title): ?RemoteWebElement
    {
        $selector = '@selectControl';

        $browser->waitFor($selector);
        $browser->waitFor("$selector .select-control-options");

        $list = $browser
            ->element($selector)
            ->findElement(WebDriverBy::xpath(".//*[@class='select-control-options']"));

        $element = \Illuminate\Support\Arr::first(
            $list->findElements(WebDriverBy::xpath(".//*[@class='select-control-option']")),
            fn (RemoteWebElement $element) => trim($element->getText()) === $title
        );

        $this->assertNotNull($element);

        return $element;
    }

    /**
     * @param Organization $organization
     * @param array $settings
     * @return Fund
     */
    protected function createFundAndConfigure(Organization $organization, array $settings): Fund
    {
        $fund = $this->makeTestFund($organization, $settings['fund'], $settings['fund_config']);

        $fund->criteria()->delete();

        foreach ($settings['fund_criteria'] as $criterion) {
            $stepTitle = Arr::get($criterion, 'step', Arr::get($criterion, 'step.title'));
            $stepFields = is_array(Arr::get($criterion, 'step')) ? Arr::get($criterion, 'step') : [];

            /** @var FundCriteriaStep $stepModel */
            $stepModel = $stepTitle ?
                ($fund->criteria_steps()->firstWhere([
                    'title' => $stepTitle,
                    ...$stepFields,
                ]) ?: $fund->criteria_steps()->forceCreate([
                    'title' => $stepTitle,
                    ...$stepFields,
                ])) : null;

            /** @var FundCriterion $criterionModel */
            $criterionModel = $fund->criteria()->create([
                ...array_except($criterion, ['rules', 'step']),
                'fund_criteria_step_id' => $stepModel?->id,
            ]);

            foreach ($criterion['rules'] ?? [] as $rule) {
                $criterionModel->fund_criterion_rules()->forceCreate($rule);
            }
        }

        $fundFormula = [[
            'type' => 'fixed',
            'amount' => $fund->isTypeBudget() ? 600 : 0,
            'fund_id' => $fund->id,
        ]];

        $fund->fund_formulas()->delete();
        $fund->fund_formulas()->createMany($fundFormula);

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        return $fund->refresh();
    }

    /**
     * @param array $settings
     * @return void
     */
    protected function configureRecordTypes(array $settings): void
    {
        array_walk($settings, function ($value) {
            RecordType::where('key', $value['key'])->update($value);
        });
    }
}
