<?php

namespace Browser;

use App\Helpers\Arr;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\RecordType;
use App\Models\Voucher;
use App\Services\DigIdService\Models\DigIdSession;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Laravel\Dusk\Browser;
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
     * Check field "gender" control type as checkbox if fund criteria operator is "="
     * and record type "control_type" attribute was ignored in this case
     *
     * @throws \Throwable
     */
    public function testWebshopFundRequestControlTypeByFundCriteria(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
        ], [
            'title' => 'Choose gender',
            'description' => 'Choose gender description',
            'record_type_key' => 'gender',
            'operator' => '=',
            'value' => 'Female',
            'show_attachment' => false,
        ], [
            'title' => 'Choose the salary',
            'description' => 'Choose the salary description',
            'record_type_key' => 'base_salary',
            'operator' => '<',
            'value' => 300,
            'show_attachment' => false,
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => true,
            'available_apply_options' => [],
            'assert_overview_titles' => [
                'Choose your municipality',
                'Choose the number of children',
                'Choose gender',
                'Choose the salary',
            ],
        ];

        $stepsData = [[
            'title' => 'Choose your municipality',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ]],
        ], [
            'title' => 'Choose the number of children',
            'fields' => [[
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
        ], [
            'title' => 'Choose gender',
            'fields' => [[
                'title' => 'Choose gender',
                'description' => 'Choose gender description',
                'type' => 'checkbox',
                'value' => true,
            ]],
        ], [
            'title' => 'Choose the salary',
            'fields' => [[
                'title' => 'Choose the salary',
                'description' => 'Choose the salary description',
                'type' => 'currency',
                'value' => 200,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * Check fields control type depends on record type "control_type" attribute
     * is set as text
     *
     * @throws \Throwable
     */
    public function testWebshopFundRequestControlTypeByRecordType(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'number',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
        ], [
            'title' => 'Choose gender',
            'description' => 'Choose gender description',
            'record_type_key' => 'gender',
            'operator' => '*',
            'show_attachment' => false,
        ], [
            'title' => 'Choose the salary',
            'description' => 'Choose the salary description',
            'record_type_key' => 'base_salary',
            'operator' => '<',
            'value' => 300,
            'show_attachment' => false,
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => true,
            'available_apply_options' => [],
            'assert_overview_titles' => [
                'Choose your municipality',
                'Choose the number of children',
                'Choose gender',
                'Choose the salary',
            ],
        ];

        $stepsData = [[
            'title' => 'Choose your municipality',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ]],
        ], [
            'title' => 'Choose the number of children',
            'fields' => [[
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'number',
                'value' => 3,
            ]],
        ], [
            'title' => 'Choose gender',
            'fields' => [[
                'title' => 'Choose gender',
                'description' => 'Choose gender description',
                'type' => 'text',
                'value' => 'Female',
            ]],
        ], [
            'title' => 'Choose the salary',
            'fields' => [[
                'title' => 'Choose the salary',
                'description' => 'Choose the salary description',
                'type' => 'currency',
                'value' => 200,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * Check criteria steps configured. Assert criteria steps are visible, criteria grouped by criteria step,
     * if criteria doesn't related to criteria step - must be on separate fund request step and
     * step title must be criteria title
     *
     * @throws \Throwable
     */
    public function testWebshopFundRequestCriteriaStepsAndSingleCriteria(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose gender',
            'description' => 'Choose gender description',
            'record_type_key' => 'gender',
            'operator' => '=',
            'value' => 'Female',
            'show_attachment' => false,
            'step' => 'Step #2',
        ], [
            'title' => 'Choose the salary',
            'description' => 'Choose the salary description',
            'record_type_key' => 'base_salary',
            'operator' => '<',
            'value' => 300,
            'show_attachment' => false,
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => true,
            'available_apply_options' => [],
            'assert_overview_titles' => [
                'Step #1',
                'Step #2',
                'Choose the salary',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
        ], [
            'title' => 'Step #2',
            'fields' => [[
                'title' => 'Choose gender',
                'description' => 'Choose gender description',
                'type' => 'checkbox',
                'value' => true,
            ]],
        ], [
            'title' => 'Choose the salary',
            'fields' => [[
                'title' => 'Choose the salary',
                'description' => 'Choose the salary description',
                'type' => 'currency',
                'value' => 200,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * Check criteria steps configured. Assert all criteria connected to criteria steps
     * and fund request steps has criteria steps titles
     *
     * @throws \Throwable
     */
    public function testWebshopFundRequestAllCriteriaConfiguredWithCriteriaSteps(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose gender',
            'description' => 'Choose gender description',
            'record_type_key' => 'gender',
            'operator' => '=',
            'value' => 'Female',
            'show_attachment' => false,
            'step' => 'Step #2',
        ], [
            'title' => 'Choose the salary',
            'description' => 'Choose the salary description',
            'record_type_key' => 'base_salary',
            'operator' => '<',
            'value' => 300,
            'show_attachment' => false,
            'step' => 'Step #3',
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => true,
            'available_apply_options' => [],
            'assert_overview_titles' => [
                'Step #1',
                'Step #2',
                'Step #3',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
        ], [
            'title' => 'Step #2',
            'fields' => [[
                'title' => 'Choose gender',
                'description' => 'Choose gender description',
                'type' => 'checkbox',
                'value' => true,
            ]],
        ], [
            'title' => 'Step #3',
            'fields' => [[
                'title' => 'Choose the salary',
                'description' => 'Choose the salary description',
                'type' => 'currency',
                'value' => 200,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * Check if criteria step with criteria "children_nth" not visible on start screen
     * in steps list as it depends on "municipality" value
     *
     * @throws \Throwable
     */
    public function testWebshopConditionalStepOverviewVisibility(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => [
                'title' => 'Step #1',
                'description' => 'The _short_ __description__ of the step.',
            ],
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose gender',
            'description' => 'Choose gender description',
            'record_type_key' => 'gender',
            'operator' => '=',
            'value' => 'Female',
            'show_attachment' => false,
            'step' => 'Step #2',
            'rules' => [[
                'record_type_key' => 'municipality',
                'operator' => '=',
                'value' => '268',
            ]]
        ], [
            'title' => 'Choose the salary',
            'description' => 'Choose the salary description',
            'record_type_key' => 'base_salary',
            'operator' => '<',
            'value' => 300,
            'show_attachment' => false,
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => true,
            'available_apply_options' => [],
            'assert_overview_titles' => [
                'Step #1',
                'Choose the salary',
            ],
            'assert_overview_titles_missed' => [
                'Step #2',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
        ], [
            'title' => 'Step #2',
            'fields' => [[
                'title' => 'Choose gender',
                'description' => 'Choose gender description',
                'type' => 'checkbox',
                'value' => true,
            ]],
        ], [
            'title' => 'Choose the salary',
            'fields' => [[
                'title' => 'Choose the salary',
                'description' => 'Choose the salary description',
                'type' => 'currency',
                'value' => 200,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * Check conditional criteria steps - as example was taken two fields "net_worth" record,
     * and it depends on "children_nth" record value, for value 5-9 we show one "net_worth" field,
     * for 10+ another "net_worth" field. In this test we fill "children_nth" not in condition range
     * with value "3", so all "net_worth" fields must be missed.
     *
     * @throws \Throwable
     */
    public function testWebshopFundRequestCriteriaNotVisibleByCondition(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => [
                'title' => 'Step #1',
                'description' => 'The _short_ __description__ of the step.',
            ],
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Income for 5-9 children',
            'description' => 'Income for 5-9 children description',
            'record_type_key' => 'net_worth',
            'operator' => '<=',
            'value' => 1000,
            'show_attachment' => false,
            'step' => 'Step #1',
            'rules' => [[
                'record_type_key' => 'children_nth',
                'operator' => '>=',
                'value' => '5',
            ], [
                'record_type_key' => 'children_nth',
                'operator' => '<=',
                'value' => '9',
            ]]
        ], [
            'title' => 'Income for 10+ children',
            'description' => 'Income for 10+ children description',
            'record_type_key' => 'net_worth',
            'operator' => '<=',
            'value' => 2000,
            'show_attachment' => false,
            'step' => 'Step #1',
            'rules' => [[
                'record_type_key' => 'children_nth',
                'operator' => '>=',
                'value' => '10',
            ]]
        ], [
            'title' => 'Choose gender',
            'description' => 'Choose gender description',
            'record_type_key' => 'gender',
            'operator' => '=',
            'value' => 'Female',
            'show_attachment' => false,
            'step' => 'Step #2',
        ], [
            'title' => 'Choose the salary',
            'description' => 'Choose the salary description',
            'record_type_key' => 'base_salary',
            'operator' => '<',
            'value' => 300,
            'show_attachment' => false,
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => true,
            'available_apply_options' => [],
            'assert_overview_titles' => [
                'Step #1',
                'Step #2',
                'Choose the salary',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
            'missed_fields' => [
                'Income for 5-9 children',
                'Income for 10+ children',
            ],
        ], [
            'title' => 'Step #2',
            'fields' => [[
                'title' => 'Choose gender',
                'description' => 'Choose gender description',
                'type' => 'checkbox',
                'value' => true,
            ]],
        ], [
            'title' => 'Choose the salary',
            'fields' => [[
                'title' => 'Choose the salary',
                'description' => 'Choose the salary description',
                'type' => 'currency',
                'value' => 200,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * Check conditional criteria steps - as example was taken two fields "net_worth" record,
     * and it depends on "children_nth" record value, for value 5-9 we show one "net_worth" field,
     * for 10+ another "net_worth" field. In this test we fill "children_nth" in condition range
     * with value "6", so first "net_worth" field (for 5-9) must be visible and other one - not.
     *
     * @throws \Throwable
     */
    public function testWebshopFundRequestCriteriaIsVisibleByCondition(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ], [
            'key' => 'net_worth',
            'control_type' => 'number',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => [
                'title' => 'Step #1',
                'description' => 'The _short_ __description__ of the step.',
            ],
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Income for 5-9 children',
            'description' => 'Income for 5-9 children description',
            'record_type_key' => 'net_worth',
            'operator' => '<=',
            'value' => 1000,
            'show_attachment' => false,
            'step' => 'Step #1',
            'rules' => [[
                'record_type_key' => 'children_nth',
                'operator' => '>=',
                'value' => '5',
            ], [
                'record_type_key' => 'children_nth',
                'operator' => '<=',
                'value' => '9',
            ]]
        ], [
            'title' => 'Income for 10+ children',
            'description' => 'Income for 10+ children description',
            'record_type_key' => 'net_worth',
            'operator' => '<=',
            'value' => 2000,
            'show_attachment' => false,
            'step' => 'Step #1',
            'rules' => [[
                'record_type_key' => 'children_nth',
                'operator' => '>=',
                'value' => '10',
            ]]
        ], [
            'title' => 'Choose gender',
            'description' => 'Choose gender description',
            'record_type_key' => 'gender',
            'operator' => '=',
            'value' => 'Female',
            'show_attachment' => false,
            'step' => 'Step #2',
        ], [
            'title' => 'Choose the salary',
            'description' => 'Choose the salary description',
            'record_type_key' => 'base_salary',
            'operator' => '<',
            'value' => 300,
            'show_attachment' => false,
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => true,
            'available_apply_options' => [],
            'assert_overview_titles' => [
                'Step #1',
                'Step #2',
                'Choose the salary',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 6,
            ], [
                'title' => 'Income for 5-9 children',
                'description' => 'Income for 5-9 children description',
                'type' => 'number',
                'value' => 500,
            ]],
            'missed_fields' => [
                'Income for 10+ children',
            ],
        ], [
            'title' => 'Step #2',
            'fields' => [[
                'title' => 'Choose gender',
                'description' => 'Choose gender description',
                'type' => 'checkbox',
                'value' => true,
            ]],
        ], [
            'title' => 'Choose the salary',
            'fields' => [[
                'title' => 'Choose the salary',
                'description' => 'Choose the salary description',
                'type' => 'currency',
                'value' => 200,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * Assert visible apply option digid and code as it configured
     *
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionDigidWithSeveralOptions(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only([
            'digid_enabled', 'digid_required', 'digid_connection_type', 'digid_app_id',
            'digid_shared_secret', 'digid_a_select_server'
        ]);

        $implementation->forceFill([
            'digid_enabled' => true,
            'digid_required' => true,
            'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
            'digid_app_id' => 'test',
            'digid_shared_secret' => 'test',
            'digid_a_select_server' => 'test',
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ]]);

        $applyCase = [
            'apply_option' => 'digid',
            'skip_apply_option_select' => false,
            'available_apply_options' => [
                'digid', 'code',
            ],
            'assert_overview_titles' => [
                'Step #1',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionDigidWithOnlyDigidOption(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only([
            'digid_enabled', 'digid_required', 'digid_connection_type', 'digid_app_id',
            'digid_shared_secret', 'digid_a_select_server'
        ]);

        $implementation->forceFill([
            'digid_enabled' => true,
            'digid_required' => true,
            'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
            'digid_app_id' => 'test',
            'digid_shared_secret' => 'test',
            'digid_a_select_server' => 'test',
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ]]);

        $applyCase = [
            'apply_option' => 'digid',
            'skip_apply_option_select' => false,
            'available_apply_options' => [
                'digid',
            ],
            'assert_overview_titles' => [
                'Step #1',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionRequestSkipped(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => true,
            'available_apply_options' => [],
            'assert_overview_titles' => [
                'Step #1',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionRequest(): void
    {
        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');

        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        // configure record types and store previous types to reset it after test
        $prevRecordTypes = $this->configureRecordTypes([[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        $this->makeFundCriteria($fund, [[
            'title' => 'Choose your municipality',
            'description' => 'Choose your municipality description',
            'record_type_key' => 'municipality',
            'operator' => '=',
            'value' => '268',
            'show_attachment' => false,
            'step' => 'Step #1',
        ], [
            'title' => 'Choose the number of children',
            'description' => 'Choose the number of children description',
            'record_type_key' => 'children_nth',
            'operator' => '>',
            'value' => 2,
            'show_attachment' => false,
            'step' => 'Step #1',
        ]]);

        $applyCase = [
            'apply_option' => 'request',
            'skip_apply_option_select' => false,
            'available_apply_options' => [
                'request', 'code',
            ],
            'assert_overview_titles' => [
                'Step #1',
            ],
        ];

        $stepsData = [[
            'title' => 'Step #1',
            'fields' => [[
                'title' => 'Choose your municipality',
                'description' => 'Choose your municipality description',
                'type' => 'select',
                'value' => 'Nijmegen',
            ], [
                'title' => 'Choose the number of children',
                'description' => 'Choose the number of children description',
                'type' => 'step',
                'value' => 3,
            ]],
        ]];

        $this->processFundRequestTestCase($implementation, $fund, $applyCase, $stepsData);

        $this->deleteFund($fund);

        // reset models to previous attributes
        $this->rollbackRecordTypes($prevRecordTypes);
        $implementation->forceFill($implementationData)->save();
    }

    /**
     * @throws \Throwable
     */
    public function testWebshopFundRequestApplyOptionCode(): void
    {
        $now = now();

        // Configure implementation and fund
        $implementation = Implementation::byKey('nijmegen');
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);
        $organization = $implementation->organization;

        // configure implementation and organization
        $implementationData = $implementation->only('digid_enabled', 'digid_required');

        $implementation->forceFill([
            'digid_enabled' => false,
            'digid_required' => false,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'outcome_type' => 'voucher',
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        $this->addTestCriteriaToFund($fund);
        $prevalidation = $this->makePrevalidationForTestCriteria($implementation->organization, $fund);

        $this->processApplyWithCodeTestCase($implementation, $fund, $prevalidation);

        $this->deleteFund($fund);
        $implementation->forceFill($implementationData)->save();
        RecordType::where('created_at', '>=', $now)->delete();
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param array $applyCase
     * @param array $stepsData
     * @return void
     * @throws \Throwable
     */
    protected function processFundRequestTestCase(
        Implementation $implementation,
        Fund $fund,
        array $applyCase,
        array $stepsData
    ): void {
        $requester = $this->makeIdentity($this->makeUniqueEmail());

        if ($applyCase['apply_option'] === 'digid') {
            $requester->setBsnRecord('12345678');
        }

        $this->browse(function (Browser $browser) use (
            $implementation, $fund, $requester, $applyCase, $stepsData
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
            if (!$applyCase['skip_apply_option_select']) {
                foreach ($applyCase['available_apply_options'] as $applyOption) {
                    $selector = $this->getApplyOptionSelector($applyOption);
                    $selector && $browser->waitFor($selector);
                }

                $selector = $this->getApplyOptionSelector($applyCase['apply_option']);
                $selector && $browser->waitFor($selector)->click($selector);
            }

            $browser
                ->waitFor('@criteriaStepsOverview')
                ->within('@criteriaStepsOverview', function (Browser $b) use ($applyCase) {
                    array_walk($applyCase['assert_overview_titles'], fn($title) => $b->assertSee($title));

                    if (isset($applyCase['assert_overview_titles_missed'])) {
                        array_walk($applyCase['assert_overview_titles_missed'], fn($title) => $b->assertDontSee($title));
                    }
                });

            $browser->waitFor('@nextStepButton')->click('@nextStepButton');

            $this->fillRequestForm($browser, $stepsData);

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
     * @param array $stepsData
     * @return void
     * @throws TimeoutException
     */
    protected function fillRequestForm(Browser $browser, array $stepsData): void
    {
        $browser->waitFor('@fundRequestForm');

        $browser->within('@fundRequestForm', function (Browser $browser) use ($stepsData) {
            foreach ($stepsData as $step) {
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

                foreach ($step['missed_fields'] ?? [] as $field) {
                    $browser->assertDontSee($field);
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
     * @param Implementation $implementation
     * @param Fund $fund
     * @param Prevalidation $prevalidation
     * @return void
     * @throws \Throwable
     */
    protected function processApplyWithCodeTestCase(
        Implementation $implementation,
        Fund $fund,
        Prevalidation $prevalidation
    ): void {
        $requester = $this->makeIdentity($this->makeUniqueEmail());

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
     * @param array $recordTypes
     * @return void
     */
    protected function rollbackRecordTypes(array $recordTypes): void
    {
        array_walk($recordTypes, function ($type) {
            RecordType::where('id', $type['id'])->update(Arr::only($type, [
                'key', 'type', 'system', 'criteria', 'vouchers', 'organization_id', 'control_type',
            ]));
        });
    }

    /**
     * @param array $recordTypesConfigs
     * @return array
     */
    protected function configureRecordTypes(array $recordTypesConfigs): array
    {
        $recordTypes = RecordType::all()->toArray();

        array_walk($recordTypesConfigs, function ($value) {
            RecordType::where('key', $value['key'])->update($value);
        });

        return $recordTypes;
    }
}
