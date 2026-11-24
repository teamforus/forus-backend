<?php

return [
    'state_active' => 'Dit fonds is verlopen.',
    'state_closed' => 'Dit fonds is niet gesloten',
    'state_paused' => 'Dit fonds is gepauzeerd.',
    'state_pending' => 'Dit fonds is niet actief',
    'state_waiting' => 'Dit fonds is nog niet beschikbaar.',
    'not_configured' => 'Dit fonds is nog niet ingesteld.',
    'type_external' => 'Dit fonds is niet actief',
    'taken_by_partner' => 'Uw partner heeft dit tegoed al aangevraagd!',
    'backoffice_error' => 'De backoffice-API van de sponsor reageert momenteel niet. Probeer het later opnieuw.',
    'already_received' => 'U heeft dit tegoed al ontvangen. Bekijk uw tegoeden bij "Mijn tegoeden".',
    'unmet_criteria' => 'U voldoet nog niet aan alle voorwaarden voor dit fonds.',
    'profile_payout_not_supported' => 'Fondsen voor uitbetalingen worden niet ondersteund bij het aanmaken ' .
        'van tegoeden op basis van persoonsgegevens.',
    'profile_award_failed' => 'Er kunnen geen tegoeden worden aangemaakt voor deze persoon en dit fonds.',
    'profile_award_created' => 'Tegoeden aangemaakt.',
    'profile_award_preview_eligible' => 'Deze persoon voldoet aan de voorwaarden voor tegoeden uit dit fonds.',
    'profile_award_preview_failed' => 'De tegoeden voor deze persoon en dit fonds kunnen niet worden geschat.',
    'profile_award_not_eligible' => 'Deze persoon voldoet niet aan de voorwaarden van dit fonds.',
    'profile_award_no_matching_formulas' => 'Er zijn geen tegoeden van toepassing op de beschikbare gegevenswaarden.',
    'profile_award_calculation_failed' => 'De toekenning kon niet worden berekend.',
    'profile_award_errors' => [
        'record_type_missing' => 'Een veld van de rekenregel is niet ingesteld.',
        'input_missing' => ':name is verplicht voor deze berekening.',
        'input_not_numeric' => ':name moet een getal zijn.',
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
