<?php

namespace Tests\TestCases;

use App\Services\DigIdService\Models\DigIdSession;

class FundRequestCriteriaStepsTestCases
{
    /** @var array|array[] */
    public static array $controlTypeTestCase1 = [
        'apply_option' => 'request',
        'skip_apply_option_select' => true,
        'available_apply_options' => [],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Choose your municipality',
            'Choose the number of children',
            'Choose gender',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $controlTypeTestCase2 = [
        'apply_option' => 'request',
        'skip_apply_option_select' => true,
        'available_apply_options' => [],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'number',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Choose your municipality',
            'Choose the number of children',
            'Choose gender',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $stepTestCase1 = [
        'apply_option' => 'request',
        'skip_apply_option_select' => true,
        'available_apply_options' => [],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Step #1',
            'Step #2',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $stepTestCase2 = [
        'apply_option' => 'request',
        'skip_apply_option_select' => true,
        'available_apply_options' => [],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Step #1',
            'Step #2',
            'Step #3',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $conditionalStepTestCase1 = [
        'apply_option' => 'request',
        'skip_apply_option_select' => true,
        'available_apply_options' => [],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Step #1',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $conditionalStepTestCase2 = [
        'apply_option' => 'request',
        'skip_apply_option_select' => true,
        'available_apply_options' => [],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ],
        'record_types' => [[
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
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Step #1',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $applyDigidTestCase = [
        'apply_option' => 'digid',
        'skip_apply_option_select' => false,
        'available_apply_options' => [
            'digid', 'code',
        ],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => true,
            'digid_required' => true,
            'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
            'digid_app_id' => 'test',
            'digid_shared_secret' => 'test',
            'digid_a_select_server' => 'test',
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Step #1',
            'Step #2',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $applyDigidTestCase2 = [
        'apply_option' => 'digid',
        'skip_apply_option_select' => false,
        'available_apply_options' => [
            'digid',
        ],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => true,
            'digid_required' => true,
            'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
            'digid_app_id' => 'test',
            'digid_shared_secret' => 'test',
            'digid_a_select_server' => 'test',
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Step #1',
            'Step #2',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $applyRequestSkippedTestCase = [
        'apply_option' => 'request',
        'skip_apply_option_select' => true,
        'available_apply_options' => [],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Step #1',
            'Step #2',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $applyRequestTestCase = [
        'apply_option' => 'request',
        'skip_apply_option_select' => false,
        'available_apply_options' => [
            'request', 'code',
        ],
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ],
        'record_types' => [[
            'key' => 'municipality',
            'control_type' => 'select',
        ], [
            'key' => 'children_nth',
            'control_type' => 'step',
        ], [
            'key' => 'gender',
            'control_type' => 'text',
        ]],
        'fund_criteria' => [[
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
        ]],
        'assert_overview_titles' => [
            'Step #1',
            'Step #2',
            'Choose the salary',
        ],
        'steps_data' => [[
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
        ]],
    ];

    /** @var array|array[] */
    public static array $applyCodeTestCase = [
        'implementation' => [
            'key' => 'nijmegen',
            'digid_enabled' => false,
            'digid_required' => false,
        ],
        'fund' => [
            'type' => 'budget',
            'criteria_editable_after_start' => true,
        ],
        'fund_config' => [
            'outcome_type' => 'voucher',
            'auth_2fa_restrict_emails' => true,
            'auth_2fa_restrict_auth_sessions' => true,
            'auth_2fa_restrict_reimbursements' => true,
            'custom_amount_min' => 100,
            'custom_amount_max' => 200,
            'allow_custom_amounts' => true,
            'allow_custom_amounts_validator' => true,
            'allow_preset_amounts' => true,
            'allow_preset_amounts_validator' => true,
            'bsn_confirmation_time' => null,
            'bsn_confirmation_api_time' => null,
            'allow_direct_requests' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ],
    ];
}
