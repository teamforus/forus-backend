<?php

return [
    'fund_request_replaced' => 'Er is al een open aanvraag voor dit persoon. Behandel deze om verder te gaan.',
    'fund_not_active' => 'Fonds niet actief.',
    'approved_request_exists' => 'Geaccepteerde aanvraag bestaat al.',
    'invalid_iban_format' => 'Invalid iban format.',
    'invalid_iban_record_keys' => 'The fund is missing the iban and iban_name keys from fund_configs, both are required for payout funds.',
    'invalid_iban_record_values' => implode("\n", [
        "The request is missing the iban or iban name fields, both are required for payouts.",
        "The most likely cause for this issue could be:",
        "1) Fund request missing iban or iban_name records because they are missing from criteria",
        "2) Iban or iban_name record was individually declined",
    ]),
    'invalid_fund_request_manual_policy' => "Payout funds don't support apply_manually fund request policy.",
    'configuration_issue' => 'Configuratieprobleem.',
];
