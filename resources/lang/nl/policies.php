<?php

return [
    'misc' => [
        'unauthorized_action' => 'Deze actie is niet toegestaan.',
    ],
    'fund_requests' => [
        'pending_request_exists' => 'Er bestaat al een lopend verzoek.',
        'not_pending' => 'Verzoek is niet in behandeling.',
        'bsn_is_unknown' => 'BSN is onbekend.',
        'bsn_is_required' => 'BSN is verplicht.',
        'bsn_not_enabled' => 'BSN is niet ingeschakeld.',
        'not_assigned' => 'Niet aan jou toegewezen.',
        'iconnect_not_available' => 'IConnect is niet beschikbaar.',
        'email_is_required' => 'E-mail is verplicht.',
        'invalid_permissions' => 'Deze actie is niet toegestaan.',
        'not_note_author' => 'Alleen de auteur kan notities verwijderen.',
        'invalid_validator' => 'Ongeldige validator.',
        'invalid_endpoint' => 'Ongeldige endpoint.',
        'invalid_requester' => 'Deze actie is niet toegestaan.',
        'fund_request_replaced' => 'Er is al een open aanvraag voor dit persoon. Behandel deze om verder te gaan.',
        'fund_not_active' => 'Fonds niet actief.',
        'approved_request_exists' => 'Geaccepteerde aanvraag bestaat al.',
        'invalid_iban_format' => 'Invalid iban format.',
        'invalid_iban_record_keys' => 'The fund is missing the iban and iban_name keys from fund_configs, both are required for payout funds.',
        'invalid_iban_record_values' => implode("\n", [
            'The request is missing the iban or iban name fields, both are required for payouts.',
            'The most likely cause for this issue could be:',
            '1) Fund request missing iban or iban_name records because they are missing from criteria',
            '2) Iban or iban_name record was individually declined',
        ]),
        'invalid_fund_request_manual_policy' => "Payout funds don't support apply_manually fund request policy.",
        'configuration_issue' => 'Configuratieprobleem.',
    ],
    '2fa' => [
        'same_type_exists' => 'Je hebt al een verbinding van hetzelfde type.',
        'phone_exists' => 'Telefoonnummer is al in gebruik.',
        'invalid_provider' => 'Ongeldig provider type.',
        'connection_not_active' => 'Verbinding is niet actief.',
    ],
    'email' => [
        'already_primary' => 'Reeds primair.',
        'already_verified' => 'Je hebt je e-mailadres al geverifieerd.',
        'cant_delete_primary_email' => 'Primair e-mailadres kan niet worden verwijderd.',
        'not_verified' => 'Verifieer eerst je e-mailadres.',
        'invalid_2fa' => 'Ongeldige 2FA-status.',
    ],
    'physical_cards' => [
        'not_allowed' => 'Fysieke kaarten zijn niet toegestaan in dit fonds.',
        'already_attached' => 'Er is al een fysieke kaart gekoppeld aan deze voucher.',
        'only_budget_vouchers' => 'Deze voucher ondersteunt geen fysieke kaarten.',
    ],
    'reservations' => [
        'timeout_extra_payment' => 'Het is op dit moment niet mogelijk om uw reservering te annuleren. Probeer het om :time.',
        'not_waiting' => 'Betaling voor reservering wacht niet.',
        'extra_payment_invalid' => 'Ongeldige reservering.',
        'extra_payment_is_paid' => 'Extra betaling is al voldaan.',
        'extra_payment_time_expired' => 'Betaaltijd is verstreken.',
    ],
    'prevalidations' => [
        'used' => [
            'title' => 'Activeringscode al gebruikt',
            'message' => 'Deze activeringscode is al gebruikt. Gebruik een andere code..',
        ],
        'used_same_fund' => [
            'title' => 'U heeft een voucher voor deze regeling!',
            'message' => implode('', [
                'Gebruik voor iedere individuele aanvraag een apart account. ' .
                'Wilt u een tweede code activeren, gebruik hiervoor een nieuw e-mailadres.',
            ]),
        ],
    ],
    'reimbursements' => [
        'not_draft' => 'Alleen conceptverzoeken kunnen worden geannuleerd.',
        'not_pending' => [
            'title' => 'Niet langer in behandeling',
            'message' => 'Het declaratieverzoek is niet meer in behandeling.',
        ],
        'not_assigned' => [
            'title' => 'Declaratieverzoek niet toegewezen',
            'message' => 'Het declaratieverzoek is niet (langer) aan jou toegewezen.',
        ],
        'already_assigned' => [
            'title' => 'Declaratieverzoek reeds toegewezen',
            'message' => 'Het declaratieverzoek is al aan iemand anders toegewezen.',
        ],
    ],
];
