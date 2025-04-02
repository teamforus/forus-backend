<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\RecordType;
use App\Models\Voucher;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestCriteriaStepsTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestControlTypes(): void
    {
        $this->checkControlTypes('string', [
            'text' => [
                '*' => [
                    'value' => 'any',
                    'assert_valid' => 'something',
                ],
            ],
        ]);

        $this->checkControlTypes('bool', [
            'checkbox' => [
                '*' => [
                    'value' => 'Ja',
                    'assert_valid' => true,
                    'assert_invalid' => false,
                ],
                '=' => [
                    'value' => 'Nee',
                    'assert_valid' => true,
                    'assert_invalid' => false,
                ],
            ],
        ]);

        $this->checkControlTypes('email', [
            'text' => [
                '*' => [
                    'value' => '',
                    'assert_valid' => $this->faker->email,
                    'assert_invalid' => 'invalid_email',
                ],
            ],
        ]);

        $this->checkControlTypes('iban', [
            'text' => [
                '*' => [
                    'value' => '',
                    'assert_valid' => $this->faker->iban,
                    'assert_invalid' => 'invalid_iban',
                ],
            ],
        ]);

        $this->checkControlTypes('number', [
            'text' => [
                '<' => [
                    'value' => 10,
                    'assert_valid' => 5,
                    'assert_invalid' => 15,
                ],
                '<=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 15,
                ],
                '=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 15,
                ],
                '>=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 5,
                ],
                '>' => [
                    'value' => 10,
                    'assert_valid' => 15,
                    'assert_invalid' => 5,
                ],
                '*' => [
                    'value' => '',
                    'assert_valid' => 5,
                ],
            ],
            'number' => [
                '<' => [
                    'value' => 10,
                    'assert_valid' => 5,
                    'assert_invalid' => 15,
                ],
                '<=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 15,
                ],
                '=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 15,
                ],
                '>=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 5,
                ],
                '>' => [
                    'value' => 10,
                    'assert_valid' => 15,
                    'assert_invalid' => 5,
                ],
                '*' => [
                    'value' => '',
                    'assert_valid' => 5,
                ],
            ],
            'currency' => [
                '<' => [
                    'value' => 10,
                    'assert_valid' => 5,
                    'assert_invalid' => 15,
                ],
                '<=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 15,
                ],
                '=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 15,
                ],
                '>=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 5,
                ],
                '>' => [
                    'value' => 10,
                    'assert_valid' => 15,
                    'assert_invalid' => 5,
                ],
                '*' => [
                    'value' => '',
                    'assert_valid' => 5,
                ],
            ],
            'step' => [
                '<' => [
                    'value' => 10,
                    'assert_valid' => 5,
                    'assert_invalid' => 15,
                ],
                '<=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 15,
                ],
                '=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 15,
                ],
                '>=' => [
                    'value' => 10,
                    'assert_valid' => 10,
                    'assert_invalid' => 5,
                ],
                '>' => [
                    'value' => 10,
                    'assert_valid' => 15,
                    'assert_invalid' => 5,
                ],
                '*' => [
                    'value' => '',
                    'assert_valid' => 5,
                ],
            ],
        ]);

        $this->checkControlTypes('select', [
            'select' => [
                '=' => [
                    'value' => 'option 1',
                    'options' => [[
                        'value' => 'option 1',
                        'name' => 'option 1',
                    ], [
                        'value' => 'option 2',
                        'name' => 'option 2',
                    ]],
                    'assert_valid' => 'option 1',
                    'assert_invalid' => 'option 2',
                ],
                '*' => [
                    'value' => 'option 1',
                    'options' => [[
                        'value' => 'option 1',
                        'name' => 'option 1',
                    ], [
                        'value' => 'option 2',
                        'name' => 'option 2',
                    ]],
                    'assert_valid' => 'option 2',
                ],
            ],
        ]);

        $this->checkControlTypes('select_number', [
            'select' => [
                '=' => [
                    'value' => 1,
                    'options' => [[
                        'value' => 1,
                        'name' => '1',
                    ], [
                        'value' => 2,
                        'name' => '2',
                    ]],
                    'assert_valid' => '1',
                    'assert_invalid' => '2',
                ],
                '>=' => [
                    'value' => 2,
                    'options' => [[
                        'value' => 1,
                        'name' => '1',
                    ], [
                        'value' => 2,
                        'name' => '2',
                    ]],
                    'assert_valid' => '2',
                    'assert_invalid' => '1',
                ],
                '<=' => [
                    'value' => 2,
                    'options' => [[
                        'value' => 1,
                        'name' => '1',
                    ], [
                        'value' => 2,
                        'name' => '2',
                    ], [
                        'value' => 3,
                        'name' => '3',
                    ]],
                    'assert_valid' => '2',
                    'assert_invalid' => '3',
                ],
                '*' => [
                    'value' => 1,
                    'options' => [[
                        'value' => 1,
                        'name' => '1',
                    ], [
                        'value' => 2,
                        'name' => '2',
                    ]],
                    'assert_valid' => '2',
                ],
            ],
        ]);

        $this->checkControlTypes('date', [
            'text' => [
                '>' => [
                    'value' => '01-01-2024',
                    'assert_valid' => '01-02-2024',
                    'assert_invalid' => '2023-12-31',
                ],
                '=' => [
                    'value' => '01-01-2024',
                    'assert_valid' => '01-01-2024',
                    'assert_invalid' => '2023-12-31',
                ],
            ],
            'date' => [
                '>' => [
                    'value' => '01-01-2024',
                    'assert_valid' => '01-02-2024',
                    'assert_invalid' => '2023-12-31',
                ],
                '=' => [
                    'value' => '01-01-2024',
                    'assert_valid' => '01-01-2024',
                    'assert_invalid' => '2023-12-31',
                ],
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestCriteriaSteps(): void
    {
        $configs = [[
            'record_type' => 'string',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'text',
            'operator' => '*',
            'value' => 'any',
            'assert_valid' => 'something',
            'step' => 'Step #1',
        ], [
            'record_type' => 'number',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'number',
            'operator' => '=',
            'value' => 10,
            'assert_valid' => 10,
            'assert_invalid' => 12,
            'step' => 'Step #1',
        ], [
            'record_type' => 'email',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'text',
            'operator' => '*',
            'value' => '',
            'assert_valid' => $this->faker->email,
            'step' => 'Step #2',
        ], [
            'record_type' => 'string',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'text',
            'operator' => '*',
            'value' => 'any',
            'assert_valid' => 'another single field without step',
            'step' => null,
        ]];

        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $criteria = [];
        $recordTypes = [];

        array_walk($configs, function ($config) use ($organization, &$criteria, &$recordTypes) {
            $recordType = RecordType::create([
                'key' => $config['record_key'],
                'type' => $config['record_type'],
                'criteria' => true,
                'control_type' => $config['control_type'],
                'organization_id' => $organization->id,
            ]);

            $recordTypes[] = $recordType;

            $criteria[] = [
                'step' => $config['step'],
                'title' => "Choose item $recordType->key",
                'value' => $config['value'],
                'operator' => $config['operator'],
                'description' => "Choose item $recordType->key description",
                'assert_valid' => $config['assert_valid'],
                'assert_control' => $recordType->control_type,
                'assert_invalid' => $config['assert_invalid'] ?? null,
                'record_type_key' => $recordType->key,
                'show_attachment' => false,
            ];
        });

        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
            [$organization, $organization->only(['fund_request_resolve_policy'])],
        ], function () use ($implementation, $organization, $fund, $criteria) {
            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            $this->makeFundCriteria($fund, $criteria);
            $this->processFundRequestTestCase($implementation, $fund, $criteria);
        }, function () use ($fund, $recordTypes) {
            $fund && $this->deleteFund($fund);
            array_walk($recordTypes, fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestConditionalSteps(): void
    {
        $textRecordTypeKey = token_generator()->generate(16);
        $numberRecordTypeKey = token_generator()->generate(16);

        $configs = [[
            'record_type' => 'string',
            'record_key' => $textRecordTypeKey,
            'control_type' => 'text',
            'operator' => '*',
            'value' => 'any',
            'assert_valid' => 'something',
            'step' => 'Step #1',
        ], [
            'record_type' => 'number',
            'record_key' => $numberRecordTypeKey,
            'control_type' => 'number',
            'operator' => '=',
            'value' => 10,
            'assert_valid' => 10,
            'assert_invalid' => 12,
            'step' => 'Step #1',
            'rules' => [[
                'record_type_key' => $textRecordTypeKey,
                'operator' => '=',
                'value' => 'something',
            ]],
        ], [
            'record_type' => 'number',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'step',
            'operator' => '>',
            'value' => 1,
            'assert_valid' => 5,
            'assert_invalid' => 1,
            'step' => 'Step #1',
            'rules' => [[
                'record_type_key' => $numberRecordTypeKey,
                'operator' => '=',
                'value' => 10,
            ]],
        ], [
            'record_type' => 'date',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'date',
            'operator' => '*',
            'value' => '',
            'assert_valid' => '01-01-2021',
            'assert_hidden_control' => true,
            'step' => 'Step #1',
            'rules' => [[
                'record_type_key' => $numberRecordTypeKey,
                'operator' => '<',
                'value' => 5,
            ]],
        ], [
            'record_type' => 'email',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'text',
            'operator' => '*',
            'value' => '',
            'assert_valid' => $this->faker->email,
            'step' => 'Step #2',
            'assert_hidden_step' => false,
            'assert_hidden_step_in_overview' => true,
            'rules' => [[
                'record_type_key' => $numberRecordTypeKey,
                'operator' => '>',
                'value' => 5,
            ]],
        ], [
            'record_type' => 'email',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'text',
            'operator' => '*',
            'value' => '',
            'assert_valid' => $this->faker->email,
            'step' => 'Step #3',
            'assert_hidden_step' => true,
            'assert_hidden_step_in_overview' => true,
            'rules' => [[
                'record_type_key' => $numberRecordTypeKey,
                'operator' => '=',
                'value' => 15,
            ]],
        ]];

        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $criteria = [];
        $recordTypes = [];

        array_walk($configs, function ($config) use ($organization, &$criteria, &$recordTypes) {
            $recordType = RecordType::create([
                'key' => $config['record_key'],
                'type' => $config['record_type'],
                'criteria' => true,
                'control_type' => $config['control_type'],
                'organization_id' => $organization->id,
            ]);

            $recordTypes[] = $recordType;

            $criteria[] = [
                'title' => "Choose item $recordType->key",
                'description' => "Choose item $recordType->key description",
                'step' => $config['step'],
                'value' => $config['value'],
                'rules' => $config['rules'] ?? [],
                'operator' => $config['operator'],
                'assert_valid' => $config['assert_valid'],
                'assert_invalid' => $config['assert_invalid'] ?? null,
                'assert_control' => $recordType->control_type,
                'record_type_key' => $recordType->key,
                'show_attachment' => false,
                'assert_hidden_control' => $config['assert_hidden_control'] ?? false,
                'assert_hidden_step' => $config['assert_hidden_step'] ?? false,
                'assert_hidden_step_in_overview' => $config['assert_hidden_step_in_overview'] ?? false,
            ];
        });

        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
            [$organization, $organization->only(['fund_request_resolve_policy'])],
        ], function () use ($implementation, $organization, $fund, $criteria) {
            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            $this->makeFundCriteria($fund, $criteria);
            $this->processFundRequestTestCase($implementation, $fund, $criteria);
        }, function () use ($fund, $recordTypes) {
            $fund && $this->deleteFund($fund);
            array_walk($recordTypes, fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestApplyOptionsRequestAndCode(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization, [
            'type' => 'budget',
        ], [
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
        ], function () use ($implementation, $fund) {
            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            $requester = $this->makeIdentity($this->makeUniqueEmail());

            $this->browse(function (Browser $browser) use (
                $implementation,
                $requester,
                $fund,
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

                $browser->waitFor('@requestOption');
                $browser->assertPresent('@requestOption');
                $browser->assertPresent('@codeOption');
                $browser->assertMissing('@digidOption');

                // Logout user
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestApplyOptionCode(): void
    {
        $now = now();

        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
            [$organization, $organization->only(['fund_request_resolve_policy'])],
        ], function () use ($implementation, $organization, $fund) {
            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $this->addTestCriteriaToFund($fund);
            $prevalidation = $this->makePrevalidationForTestCriteria($implementation->organization, $fund);

            $this->processApplyWithCodeTestCase($implementation, $fund, $prevalidation);
        }, function () use ($fund, $now) {
            $fund && $this->deleteFund($fund);
            RecordType::where('created_at', '>=', $now)->delete();
        });
    }

    /**
     * @throws Throwable
     */
    protected function checkControlTypes(string $inputType, array $criteriaConfigs = []): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $recordTypes = collect($criteriaConfigs)
            ->mapWithKeys(function ($criteriaConfigs, $controlType) use ($organization, $inputType) {
                $operatorRecordTypes = [];

                foreach ($criteriaConfigs as $operator => $criteriaConfig) {
                    $recordType = RecordType::create([
                        'key' => token_generator()->generate(16),
                        'type' => $inputType,
                        'criteria' => true,
                        'control_type' => $controlType,
                        'organization_id' => $organization->id,
                    ]);

                    foreach ($criteriaConfig['options'] ?? [] as $option) {
                        $recordType
                            ->record_type_options()
                            ->create($option)
                            ->translateOrNew('nl')
                            ->fill($option)
                            ->save();
                    }

                    $operatorRecordTypes[$operator] = $recordType;
                }

                return [$controlType => $operatorRecordTypes];
            });

        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
            [$organization, $organization->only(['fund_request_resolve_policy'])],
        ], function () use ($implementation, $organization, $fund, $criteriaConfigs, $recordTypes) {
            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            $criteria = [];

            /**
             * @var string $operator
             * @var RecordType $recordType
             */
            foreach ($recordTypes as $controlType => $criteriaConfig) {
                foreach ($criteriaConfig as $operator => $recordType) {
                    $criteriaConfig = $criteriaConfigs[$controlType][$operator];

                    $criteria[] = [
                        'step' => null,
                        'value' => $criteriaConfig['value'],
                        'title' => "Choose item $recordType->key",
                        'operator' => $operator,
                        'description' => "Choose item $recordType->key description",
                        'assert_valid' => $criteriaConfig['assert_valid'],
                        'assert_control' => $controlType,
                        'assert_invalid' => $criteriaConfig['assert_invalid'] ?? null,
                        'record_type_key' => $recordType->key,
                        'show_attachment' => false,
                    ];
                }
            }

            $this->makeFundCriteria($fund, $criteria);
            $this->processFundRequestTestCase($implementation, $fund, $criteria);
        }, function () use ($fund, $recordTypes) {
            $fund && $this->deleteFund($fund);
            $recordTypes->each(fn (array $recordTypes) => array_walk(
                $recordTypes,
                fn (RecordType $recordType) => $recordType->delete()
            ));
        });
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param array $criteria
     * @throws Throwable
     * @return void
     */
    protected function processFundRequestTestCase(
        Implementation $implementation,
        Fund $fund,
        array $criteria,
    ): void {
        $requester = $this->makeIdentity($this->makeUniqueEmail());

        $this->browse(function (Browser $browser) use (
            $implementation,
            $requester,
            $criteria,
            $fund,
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

            // assert steps overview
            $browser
                ->waitFor('@criteriaStepsOverview')
                ->within('@criteriaStepsOverview', function (Browser $browser) use ($criteria) {
                    array_walk($criteria, function ($criterion) use ($browser) {
                        $title = $criterion['step'] ?: $criterion['title'];

                        $criterion['assert_hidden_step_in_overview'] ?? false
                            ? $browser->assertDontSee($title)
                            : $browser->assertSee($title);
                    });
                });

            $browser->waitFor('@nextStepButton')->click('@nextStepButton');

            $this->fillRequestForm($browser, $criteria);

            // Logout user
            $this->logout($browser);
        });

        $request = FundRequest::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->exists();

        $this->assertTrue($request);
    }

    /**
     * @param Browser $browser
     * @param array $criteria
     * @throws TimeoutException
     * @return void
     */
    protected function fillRequestForm(Browser $browser, array $criteria): void
    {
        $browser->waitFor('@fundRequestForm');

        $browser->within('@fundRequestForm', function (Browser $browser) use ($criteria) {
            // group criteria by steps
            $criteria = collect($criteria);
            $singleCriteria = $criteria->whereNull('step')->map(fn ($criterion) => [$criterion]);
            $criteriaSteps = $criteria->whereNotNull('step')->groupBy('step');
            $data = $criteriaSteps->merge($singleCriteria);

            foreach ($data as $step) {
                $hiddenFields = collect($step)->filter(fn ($field) => $field['assert_hidden_step'] ?? false);

                if (count($step) === count($hiddenFields)) {
                    // this step is hidden, asser don't see it and go to next step in test
                    $browser->assertDontSeeIn('.sign_up-pane-header', $step[0]['step']);
                    continue;
                }

                foreach ($step as $field) {
                    $selector = $this->getControlSelector($field['assert_control']);

                    // control can be hidden by rules
                    if ($field['assert_hidden_control'] ?? false) {
                        $browser->assertMissing($selector);
                        continue;
                    }

                    $browser->waitForTextIn('.sign_up-pane-header', $field['step'] ?: $field['title']);
                    $field['description'] && !$field['step'] && $browser->waitForText($field['description']);

                    // assert invalid value if exists
                    if ($field['assert_invalid']) {
                        $this->fillInput($browser, $field['assert_control'], $field['assert_invalid']);
                        $browser->click('@nextStepButton');
                        $browser->waitFor('.form-error');
                        // clear input
                        $selector && $this->clearCustom($browser, $selector, $field['assert_invalid']);
                    }

                    // assert valid value
                    $this->fillInput($browser, $field['assert_control'], $field['assert_valid']);
                }

                $browser->click('@nextStepButton');
            }

            // need pause before submit form - sometimes on frontend it doesn't work immediately after steps
            $browser->pause(100);

            // submit fund request form
            $browser->waitFor('@submitButton')->click('@submitButton');
            $browser->waitFor('@fundRequestSuccess');
        });
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string|int|null $value
     * @throws TimeoutException
     * @return void
     */
    protected function clearCustom(
        Browser $browser,
        string $selector,
        string|int|null $value = null
    ): void {
        if ($selector === '@controlDate') {
            return;
        }

        if ($selector === '@controlStep') {
            $browser->waitFor($selector);
            $browser->within($selector, function (Browser $browser) use ($value) {
                for ($i = 0; $i < $value; $i++) {
                    $browser->click('@decreaseStep');
                }
            });

            return;
        }

        /** @var string $value */
        $value = $browser->value($selector);
        $browser->keys($selector, ...array_fill(0, strlen($value), '{backspace}'));
    }

    /**
     * @param Browser $browser
     * @param string $control
     * @param string|int|null $value
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function fillInput(Browser $browser, string $control, string|int|null $value): void
    {
        $selector = $this->getControlSelector($control);

        switch ($control) {
            case 'select':
                $browser->waitFor($selector);
                $browser->click("$selector .select-control-search");
                $this->findOptionElement($browser, $value)->click();
                break;
            case 'number':
            case 'currency':
            case 'text':
                $browser->waitFor($selector);
                $browser->type($selector, $value);
                break;
            case 'checkbox':
                $value && $browser->waitFor($selector)->click($selector);
                break;
            case 'step':
                $browser->waitFor($selector);
                $browser->within($selector, function (Browser $browser) use ($value) {
                    for ($i = 0; $i < $value; $i++) {
                        $browser->click('@increaseStep');
                    }
                });
                break;
            case 'date':
                $browser->waitFor($selector);
                $this->clearCustom($browser, "$selector input[type='text']");
                $browser->type("$selector input[type='text']", $value);
                break;
        }
    }

    /**
     * @param string $control
     * @return string|null
     */
    protected function getControlSelector(string $control): ?string
    {
        return match ($control) {
            'step' => '@controlStep',
            'date' => '@controlDate',
            'text' => '@controlText',
            'select' => '@selectControl',
            'number' => '@controlNumber',
            'currency' => '@controlCurrency',
            'checkbox' => '@controlCheckbox',
            default => null
        };
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param Prevalidation $prevalidation
     * @throws Throwable
     * @return void
     */
    protected function processApplyWithCodeTestCase(
        Implementation $implementation,
        Fund $fund,
        Prevalidation $prevalidation
    ): void {
        $requester = $this->makeIdentity($this->makeUniqueEmail());

        $this->browse(function (Browser $browser) use (
            $implementation,
            $prevalidation,
            $requester,
            $fund,
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
    }

    /**
     * @param Browser $browser
     * @param string $title
     * @return RemoteWebElement|null
     */
    protected function findOptionElement(Browser $browser, string $title): ?RemoteWebElement
    {
        $option = null;

        $browser->elsewhereWhenAvailable('@selectControlOptions', function (Browser $browser) use (&$option, $title) {
            $xpath = WebDriverBy::xpath(".//*[contains(@class, 'select-control-option')]");
            $options = $browser->driver->findElements($xpath);
            $option = Arr::first($options, fn (RemoteWebElement $element) => trim($element->getText()) === $title);
        });

        $this->assertNotNull($option);

        return $option;
    }
}
