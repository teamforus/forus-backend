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
        $this->checkControlTypes(RecordType::TYPE_STRING, [
            RecordType::CONTROL_TYPE_TEXT => [
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
        $this->checkControlTypes(RecordType::TYPE_BOOL, [
            RecordType::CONTROL_TYPE_CHECKBOX => [
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
        $this->checkControlTypes(RecordType::TYPE_EMAIL, [
            RecordType::CONTROL_TYPE_TEXT => [
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
        $this->checkControlTypes(RecordType::TYPE_IBAN, [
            RecordType::CONTROL_TYPE_TEXT => [
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
        $this->checkControlTypes(RecordType::TYPE_NUMBER, [
            RecordType::CONTROL_TYPE_TEXT => [
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
            RecordType::CONTROL_TYPE_NUMBER => [
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
            RecordType::CONTROL_TYPE_CURRENCY => [
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
            RecordType::CONTROL_TYPE_STEP => [
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
        $this->checkControlTypes(RecordType::TYPE_SELECT, [
            RecordType::CONTROL_TYPE_SELECT => [
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
        $this->checkControlTypes(RecordType::TYPE_SELECT_NUMBER, [
            RecordType::CONTROL_TYPE_SELECT => [
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
        $this->checkControlTypes(RecordType::TYPE_DATE, [
            RecordType::CONTROL_TYPE_TEXT => [
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
            RecordType::CONTROL_TYPE_DATE => [
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
            'record_type' => RecordType::TYPE_STRING,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_TEXT,
            'operator' => '*',
            'value' => 'any',
            'assert_valid' => 'something',
            'step' => 'Step #1',
        ], [
            'record_type' => RecordType::TYPE_NUMBER,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_NUMBER,
            'operator' => '=',
            'value' => 10,
            'assert_valid' => 10,
            'assert_invalid' => 12,
            'step' => 'Step #1',
        ], [
            'record_type' => RecordType::TYPE_EMAIL,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_TEXT,
            'operator' => '*',
            'value' => '',
            'assert_valid' => $this->faker->email,
            'step' => 'Step #2',
        ], [
            'record_type' => RecordType::TYPE_STRING,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_TEXT,
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
    public function testWebshopFundRequestCriteriaGroupRequiredValidation(): void
    {
        // create organization and fund for group validation
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // create required and optional criteria groups
        $requiredGroup = $this->makeCriteriaGroup(
            $fund,
            title: 'Required group',
            description: 'Required group description',
            required: true,
        );

        $optionalGroup = $this->makeCriteriaGroup(
            $fund,
            title: 'Optional group',
            description: 'Optional group description',
            order: 2,
        );

        // define record types and criteria mapped to groups
        $recordTypes = collect([
            $this->makeCriteriaRecordType($organization, 'string', RecordType::CONTROL_TYPE_TEXT),
            $this->makeCriteriaRecordType($organization, 'string', RecordType::CONTROL_TYPE_TEXT),
            $this->makeCriteriaRecordType($organization, 'string', RecordType::CONTROL_TYPE_TEXT),
            $this->makeCriteriaRecordType($organization, 'number', RecordType::CONTROL_TYPE_NUMBER),
        ]);

        $criteria = [[
            'title' => 'Required group choice A',
            'label' => 'Required group choice A',
            'value' => 'any',
            'operator' => '*',
            'optional' => true,
            'record_type_key' => $recordTypes[0]->key,
            'fund_criteria_group_id' => $requiredGroup->id,
            'show_attachment' => false,
        ], [
            'title' => 'Required group choice B',
            'label' => 'Required group choice B',
            'value' => 'any',
            'operator' => '*',
            'optional' => true,
            'record_type_key' => $recordTypes[1]->key,
            'fund_criteria_group_id' => $requiredGroup->id,
            'show_attachment' => false,
        ], [
            'title' => 'Optional group choice',
            'label' => 'Optional group choice',
            'value' => 'any',
            'operator' => '*',
            'optional' => true,
            'record_type_key' => $recordTypes[2]->key,
            'fund_criteria_group_id' => $optionalGroup->id,
            'show_attachment' => false,
        ], [
            'title' => 'Standalone number',
            'value' => 1,
            'operator' => '>=',
            'record_type_key' => $recordTypes[3]->key,
            'show_attachment' => false,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // fetch fund resource and assert groups/links
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $response = $this->getJson(
            "/api/v1/platform/funds/$fund->id",
            $this->makeApiHeaders($this->makeIdentityProxy($identity))
        );

        $response->assertSuccessful();

        // assert group metadata and required flags
        $groups = collect($response->json('data.criteria_groups'))->keyBy('id');
        $this->assertArrayHasKey($requiredGroup->id, $groups);
        $this->assertArrayHasKey($optionalGroup->id, $groups);
        $this->assertEquals($requiredGroup->title, $groups[$requiredGroup->id]['title']);
        $this->assertEquals($optionalGroup->title, $groups[$optionalGroup->id]['title']);
        $this->assertTrue((bool) $groups[$requiredGroup->id]['required']);
        $this->assertFalse((bool) $groups[$optionalGroup->id]['required']);

        // assert criteria links to groups
        $fundCriteria = collect($response->json('data.criteria'))->keyBy('record_type_key');
        $this->assertEquals($requiredGroup->id, $fundCriteria[$recordTypes[0]->key]['fund_criteria_group_id']);
        $this->assertEquals($requiredGroup->id, $fundCriteria[$recordTypes[1]->key]['fund_criteria_group_id']);
        $this->assertEquals($optionalGroup->id, $fundCriteria[$recordTypes[2]->key]['fund_criteria_group_id']);
        $this->assertNull($fundCriteria[$recordTypes[3]->key]['fund_criteria_group_id']);

        // submit with empty required group values to trigger group error
        $recordsList = [
            $this->makeRequestCriterionValue($fund, $recordTypes[0]->key, ''),
            $this->makeRequestCriterionValue($fund, $recordTypes[1]->key, ''),
            $this->makeRequestCriterionValue($fund, $recordTypes[2]->key, ''),
            $this->makeRequestCriterionValue($fund, $recordTypes[3]->key, 5),
        ];

        $response = $this->makeFundRequest($identity, $fund, $recordsList, false);
        $response->assertJsonValidationErrors([
            "criteria_groups.$requiredGroup->id",
        ]);

        // submit with one required value filled to pass group validation
        $recordsList[0]['value'] = 'yes';

        $response = $this->makeFundRequest($identity, $fund, $recordsList, false);
        $response->assertSuccessful();
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestCriteriaGroupOrdering(): void
    {
        // create organization and fund for ordering validation
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // create groups with explicit order
        $thirdGroup = $this->makeCriteriaGroup(
            $fund,
            title: 'Group C',
            description: 'Group C description',
            order: 3,
        );

        $firstGroup = $this->makeCriteriaGroup(
            $fund,
            title: 'Group A',
            description: 'Group A description',
        );

        $secondGroup = $this->makeCriteriaGroup(
            $fund,
            title: 'Group B',
            description: 'Group B description',
            order: 2,
        );

        // fetch fund resource and assert group ordering
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $response = $this->getJson(
            "/api/v1/platform/funds/$fund->id",
            $this->makeApiHeaders($this->makeIdentityProxy($identity))
        );

        $response->assertSuccessful();

        $groupIds = array_column($response->json('data.criteria_groups'), 'id');
        $this->assertSame([$firstGroup->id, $secondGroup->id, $thirdGroup->id], $groupIds);
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestCriteriaGroupsWithSteps(): void
    {
        // create organization and fund for step/group mapping
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // create a group to attach criteria to
        $group = $this->makeCriteriaGroup(
            $fund,
            title: 'Grouped criteria',
            description: 'Grouped criteria description',
        );

        // define record types for grouped and standalone criteria
        $recordTypes = collect([
            $this->makeCriteriaRecordType($organization, 'string', RecordType::CONTROL_TYPE_TEXT),
            $this->makeCriteriaRecordType($organization, 'number', RecordType::CONTROL_TYPE_NUMBER),
        ]);

        // create criteria sharing the same step with and without group
        $criteria = [[
            'step' => 'Step #1',
            'title' => 'Grouped criterion',
            'value' => 'any',
            'operator' => '*',
            'record_type_key' => $recordTypes[0]->key,
            'fund_criteria_group_id' => $group->id,
            'show_attachment' => false,
        ], [
            'step' => 'Step #1',
            'title' => 'Standalone criterion',
            'value' => 1,
            'operator' => '>=',
            'record_type_key' => $recordTypes[1]->key,
            'show_attachment' => false,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // fetch fund resource and assert step/group links
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $response = $this->getJson(
            "/api/v1/platform/funds/$fund->id",
            $this->makeApiHeaders($this->makeIdentityProxy($identity))
        );

        $response->assertSuccessful();

        // assert step exists before checking criterion mapping
        $steps = collect($response->json('data.criteria_steps'))->keyBy('title');
        $this->assertArrayHasKey('Step #1', $steps);

        $stepId = $steps['Step #1']['id'];
        $fundCriteria = collect($response->json('data.criteria'))->keyBy('record_type_key');

        // assert grouped and standalone criteria share the same step
        $this->assertEquals($group->id, $fundCriteria[$recordTypes[0]->key]['fund_criteria_group_id']);
        $this->assertEquals($stepId, $fundCriteria[$recordTypes[0]->key]['fund_criteria_step_id']);
        $this->assertNull($fundCriteria[$recordTypes[1]->key]['fund_criteria_group_id']);
        $this->assertEquals($stepId, $fundCriteria[$recordTypes[1]->key]['fund_criteria_step_id']);
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestCriteriaMultipleRequiredGroups(): void
    {
        // create organization and fund for required group validation
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // create multiple required groups
        $groupA = $this->makeCriteriaGroup(
            $fund,
            title: 'Required group A',
            description: 'Required group A description',
            required: true,
        );

        $groupB = $this->makeCriteriaGroup(
            $fund,
            title: 'Required group B',
            description: 'Required group B description',
            order: 2,
            required: true,
        );

        // define record types for each required group
        $recordTypes = collect([
            $this->makeCriteriaRecordType($organization, 'string', RecordType::CONTROL_TYPE_TEXT),
            $this->makeCriteriaRecordType($organization, 'string', RecordType::CONTROL_TYPE_TEXT),
        ]);

        // create criteria mapped to separate required groups
        $criteria = [[
            'title' => 'Group A criterion',
            'value' => 'any',
            'operator' => '*',
            'optional' => true,
            'record_type_key' => $recordTypes[0]->key,
            'fund_criteria_group_id' => $groupA->id,
            'show_attachment' => false,
        ], [
            'title' => 'Group B criterion',
            'value' => 'any',
            'operator' => '*',
            'optional' => true,
            'record_type_key' => $recordTypes[1]->key,
            'fund_criteria_group_id' => $groupB->id,
            'show_attachment' => false,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // submit empty values and assert both group errors
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $recordsList = [
            $this->makeRequestCriterionValue($fund, $recordTypes[0]->key, ''),
            $this->makeRequestCriterionValue($fund, $recordTypes[1]->key, ''),
        ];

        $response = $this->makeFundRequest($identity, $fund, $recordsList, false);
        $response->assertJsonValidationErrors([
            "criteria_groups.$groupA->id",
            "criteria_groups.$groupB->id",
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestCriteriaGroupRequiredWithBool(): void
    {
        // create organization and fund for boolean group validation
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // create a required group
        $requiredGroup = $this->makeCriteriaGroup(
            $fund,
            title: 'Required bool group',
            description: 'Required bool group description',
            required: true,
        );

        // define bool record type and map it to the required group
        $recordType = $this->makeCriteriaRecordType($organization, 'bool', RecordType::CONTROL_TYPE_CHECKBOX);

        $criteria = [[
            'title' => 'Required checkbox',
            'label' => 'Required checkbox',
            'value' => RecordType::TYPE_BOOL_OPTION_YES,
            'operator' => '=',
            'optional' => true,
            'record_type_key' => $recordType->key,
            'fund_criteria_group_id' => $requiredGroup->id,
            'show_attachment' => false,
        ]];

        $this->makeFundCriteria($fund, $criteria);

        // submit empty checkbox and assert group error
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $recordsList = [
            $this->makeRequestCriterionValue($fund, $recordType->key, null),
        ];

        $response = $this->makeFundRequest($identity, $fund, $recordsList, false);
        $response->assertJsonValidationErrors([
            "criteria_groups.$requiredGroup->id",
        ]);

        // submit checked value and assert success
        $recordsList[0]['value'] = RecordType::TYPE_BOOL_OPTION_YES;

        $response = $this->makeFundRequest($identity, $fund, $recordsList, false);
        $response->assertSuccessful();
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestConditionalSteps(): void
    {
        $textRecordTypeKey = token_generator()->generate(16);
        $numberRecordTypeKey = token_generator()->generate(16);

        $configs = [[
            'record_type' => RecordType::TYPE_STRING,
            'record_key' => $textRecordTypeKey,
            'control_type' => RecordType::CONTROL_TYPE_TEXT,
            'operator' => '*',
            'value' => 'any',
            'input_value' => 'something',
            'step' => 'Step #1',
        ], [
            'record_type' => RecordType::TYPE_NUMBER,
            'record_key' => $numberRecordTypeKey,
            'control_type' => RecordType::CONTROL_TYPE_NUMBER,
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
            'record_type' => RecordType::TYPE_NUMBER,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_STEP,
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
            'record_type' => RecordType::TYPE_DATE,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_DATE,
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
            'record_type' => RecordType::TYPE_EMAIL,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_TEXT,
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
            'record_type' => RecordType::TYPE_EMAIL,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_TEXT,
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
            'record_type' => RecordType::TYPE_NUMBER,
            'record_key' => token_generator()->generate(16),
            'control_type' => RecordType::CONTROL_TYPE_NUMBER,
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
