<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestCriteriaStepsTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestRecordStringControlTypes(): void
    {
        $this->checkControlTypes('string', [
            'text' => [
                '*' => [
                    'value' => 'any',
                    'assert_valid' => 'something',
                ],
            ],
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestRecordBoolControlTypes(): void
    {
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
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestRecordEmailControlTypes(): void
    {
        $this->checkControlTypes('email', [
            'text' => [
                '*' => [
                    'value' => '',
                    'assert_valid' => $this->faker->email,
                    'assert_invalid' => 'invalid_email',
                ],
            ],
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestRecordIbanControlTypes(): void
    {
        $this->checkControlTypes('iban', [
            'text' => [
                '*' => [
                    'value' => '',
                    'assert_valid' => $this->faker->iban,
                    'assert_invalid' => 'invalid_iban',
                ],
            ],
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestRecordNumberControlTypes(): void
    {
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
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestRecordSelectControlTypes(): void
    {
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
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestRecordSelectNumberControlTypes(): void
    {
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
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWebshopFundRequestRecordDateControlTypes(): void
    {
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

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

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

        $this->makeFundCriteria($fund, $criteria);
        $this->assertFundResource($fund, $criteria);
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
            'input_value' => 'something',
            'step' => 'Step #1',
        ], [
            'record_type' => 'number',
            'record_key' => $numberRecordTypeKey,
            'control_type' => 'number',
            'operator' => '=',
            'value' => 10,
            'input_value' => 10,
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
            'input_value' => 5,
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
            'input_value' => '01-01-2021',
            'exclude_value' => true,
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
            'input_value' => $this->faker->email,
            'step' => 'Step #2',
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
            'input_value' => $this->faker->email,
            'step' => 'Step #3',
            'exclude_value' => true,
            'rules' => [[
                'record_type_key' => $numberRecordTypeKey,
                'operator' => '=',
                'value' => 15,
            ]],
        ], [
            'record_type' => 'number',
            'record_key' => token_generator()->generate(16),
            'control_type' => 'number',
            'operator' => '>',
            'value' => 10,
            'input_value' => 12,
            'step' => 'Step #4',
            'exclude_value' => true,
            'rules' => [[
                'record_type_key' => $numberRecordTypeKey,
                'operator' => '=',
                'value' => 15,
            ]],
        ]];

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

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
                'input_value' => $config['input_value'],
                'assert_control' => $recordType->control_type,
                'record_type_key' => $recordType->key,
                'show_attachment' => false,
                'exclude_value' => $config['exclude_value'] ?? false,
            ];
        });

        $this->makeFundCriteria($fund, $criteria);
        $this->assertFundResource($fund, $criteria);

        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $criteria = collect($criteria);
        $errors = [];

        // try to make request with all fields
        $recordsList = $criteria
            ->each(function (array $criterion, int $key) use (&$errors) {
                if ($criterion['exclude_value']) {
                    $errors[] = "records.$key.value";
                }
            })
            ->map(fn (array $criterion) => $this->makeRequestCriterionValue(
                $fund,
                $criterion['record_type_key'],
                $criterion['input_value'],
            ));

        $response = $this->makeFundRequest($identity, $fund, $recordsList, false);
        $response->assertJsonValidationErrors($errors);

        // filter hidden criteria, which are hidden by step condition
        $recordsList = $criteria
            ->filter(fn (array $criterion) => !$criterion['exclude_value'])
            ->map(fn (array $criterion) => $this->makeRequestCriterionValue(
                $fund,
                $criterion['record_type_key'],
                $criterion['input_value'],
            ));

        $response = $this->makeFundRequest($identity, $fund, $recordsList, false);
        $response->assertSuccessful();
    }

    /**
     * @throws Throwable
     */
    protected function checkControlTypes(string $inputType, array $criteriaConfigs = []): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

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

                    $recordType->record_type_options()->createMany($criteriaConfig['options'] ?? []);

                    $operatorRecordTypes[$operator] = $recordType;
                }

                return [$controlType => $operatorRecordTypes];
            });

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
        $this->assertFundResource($fund, $criteria);
    }

    /**
     * @param Fund $fund
     * @param array $criteria
     * @throws Throwable
     * @return void
     */
    protected function assertFundResource(Fund $fund, array $criteria): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));
        $response = $this->getJson("/api/v1/platform/funds/$fund->id", $headers);
        $response->assertSuccessful();

        $steps = collect($criteria)
            ->filter(fn (array $criterion) => !is_null($criterion['step']))
            ->groupBy('step')
            ->toArray();

        $fundCriteriaSteps = $response->json('data.criteria_steps');

        foreach ($fundCriteriaSteps as $fundCriteriaStep) {
            $this->assertArrayHasKey($fundCriteriaStep['title'], $steps);
        }

        $criteria = collect($criteria)->keyBy('record_type_key')->toArray();
        $fundCriteriaSteps = collect($fundCriteriaSteps)->keyBy('id')->toArray();
        $fundCriteria = $response->json('data.criteria');

        foreach ($fundCriteria as $fundCriterion) {
            $this->assertArrayHasKey($fundCriterion['record_type_key'], $criteria);
            $this->assertEquals($criteria[$fundCriterion['record_type_key']]['title'], $fundCriterion['title']);
            $this->assertEquals($criteria[$fundCriterion['record_type_key']]['description'], $fundCriterion['description']);
            $this->assertEquals($criteria[$fundCriterion['record_type_key']]['operator'], $fundCriterion['operator']);
            $this->assertEquals($criteria[$fundCriterion['record_type_key']]['value'], $fundCriterion['value']);
            $this->assertEquals($criteria[$fundCriterion['record_type_key']]['assert_control'], $fundCriterion['record_type']['control_type']);

            if ($criteria[$fundCriterion['record_type_key']]['step']) {
                $this->assertArrayHasKey($fundCriterion['fund_criteria_step_id'], $fundCriteriaSteps);
            } else {
                $this->assertNull($fundCriterion['fund_criteria_step_id']);
            }
        }
    }
}
