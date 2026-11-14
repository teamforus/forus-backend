<?php

return [
    'state_active' => 'This fund has expired.',
    'state_closed' => 'Dit fonds is niet gesloten',
    'state_paused' => 'This fund is paused.',
    'state_pending' => 'Dit fonds is niet actief',
    'state_waiting' => 'This fund is not yet available.',
    'not_configured' => 'This fund has not been configured.',
    'type_external' => 'Dit fonds is niet actief',
    'taken_by_partner' => 'Uw partner heeft dit tegoed al aangevraagd!',
    'backoffice_error' => 'De backoffice-API van de sponsor reageert momenteel niet. Probeer het later opnieuw.',
    'already_received' => 'U heeft dit tegoed al ontvangen. Bekijk uw tegoeden bij "Mijn tegoeden".',
    'unmet_criteria' => 'U voldoet nog niet aan alle voorwaarden voor dit fonds.',
    'profile_payout_not_supported' => 'Payout funds are not supported for profile-based vouchers.',
    'profile_award_failed' => 'Unable to create vouchers for this profile and fund.',
    'profile_award_created' => 'Vouchers created.',
    'profile_award_preview_eligible' => 'This profile is eligible for vouchers from this fund.',
    'profile_award_preview_failed' => 'Unable to estimate vouchers for this profile and fund.',
    'profile_award_not_eligible' => 'This profile is not eligible for this fund.',
    'profile_award_no_matching_formulas' => 'No vouchers apply to the available record values.',
    'profile_award_calculation_failed' => 'The award could not be calculated.',
    'profile_award_errors' => [
        'record_type_missing' => 'A formula field is not configured.',
        'input_missing' => ':name is required for this calculation.',
        'input_not_numeric' => ':name must be numeric.',
    ],
    'default_contact_info_message' => implode(' ', [
        'Wij verzoeken je om je contactgegevens te vermelden, zodat we je eenvoudig kunnen bereiken.',
        'Indien we tijdens de beoordeling aanvullende informatie nodig hebben, zullen we via de opgegeven contactgegevens contact met je opnemen.',
    ]),
    'types' => [
        'external' => 'External',
        'budget' => 'Budget',
    ],
    'fund_formulas' => [
        'fixed' => 'Vastgesteld',
        'multiply' => 'Multiply',
        'multiply_vouchers' => 'Multiply vouchers',
    ],
    'pre_check' => [
        'fixed_amount' => 'Vastbedrag',
        'product_voucher' => 'Product tegoed',
        'default_title' => 'Default',
        'default_title_short' => 'Default',
        'default_description' => 'Default',
    ],
];
