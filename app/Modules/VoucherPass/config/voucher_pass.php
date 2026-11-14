<?php

return [
    'columns' => [
        'external_id' => [
            'column' => 'pas_uid',
            'required' => false,
        ],
        'owner_bsn' => [
            'column' => 'pas_eigenaar',
            'required' => true,
            'normalize' => 'bsn',
        ],
        'target_bsn' => [
            'column' => 'pas_delen_met',
            'required' => true,
            'normalize' => 'bsn',
        ],
        'type_names' => [
            'column' => 'pas_type',
            'required' => false,
        ],
        'print_label' => [
            'column' => 'pas_label',
            'required' => false,
        ],
        'state' => [
            'column' => 'pas_status',
            'required' => true,
            'values' => [
                'Actief' => 'active',
                'Gedeactiveerd' => 'deactivated',
            ],
        ],
        'fund_ids' => [
            'column' => 'fund_id',
            'required' => false,
        ],
        'print_requested' => [
            'column' => 'pas_print',
            'required' => true,
            'values' => [
                'TRUE' => true,
                'FALSE' => false,
            ],
        ],
    ],
];
