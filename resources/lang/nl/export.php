<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Export Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default headers for exporting the list of 
    |   - Providers used by the sponsor class.
    |   - Transactions used by the sponsor and provider class
    | Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    
    'providers'              => [
        'fund' => 'Fondsnaam',
        'fund_type' => 'Fonds type',
        'iban' => 'IBAN',
        'provider_last_activity' => 'Laatste activiteit',
        'products_provider_count' => 'Totaal aanbiedingen beheer door aanbieder',
        'products_sponsor_count' => 'Totaal aanbiedingen beheert door sponsor',
        'products_active_count' => 'Totaal geaccepteerde aanbiedingen',
        'products_count' => 'Totaal aanbiedingen',
        'provider' => 'Aanbieder',
        'email' => 'E-mailadres',
        'phone' => 'Telefoonnummer',
        'categories' => 'Categorieën',
        'kvk' => 'KVK',
        'state' => 'Status',
        'allow_budget' => 'Geaccepteerd: budget',
        'allow_products' => 'Geaccepteerd: aanbod',
        'allow_some_products' => 'Geaccepteerd: specifiek aanbod',
    ],
    
    'fund_requests'              => [
        'bsn' => 'Burgerservicenummer',
        'fund_name' => 'Fondsnaam',
        'status' => 'Status',
        'state-values' => [
            'pending' => 'Wachtend',
            'declined' => 'Geweigerd',
            'approved' => 'Geaccepteerd',
        ],
        'validator' => 'Validator',
        'created_at' => 'Indien datum',
        'resolved_at' => 'Oplosdatum',
        'lead_time'   => 'Doorlooptijd (dagen) ',
    ],    

    'voucher_transactions'    => [
        'id' => 'ID',
        'amount' => 'bedrag',
        'date_transaction' => 'Datum betaling',
        'date_payment' => 'Datum uitbetaling',
        'fund' => 'fonds',
        'provider' => 'aanbieder',
        'state' => 'status',
        'state-values' => [
            'success' => 'voltooid', 
            'pending' => 'in afwachting',
        ]
    ],

    'prevalidations'   => [
        'code'      => 'code',
        'used'      => 'Geactiveerd',
        'used_yes'  => 'Ja',
        'used_no'   => 'Nee'
    ],

    'finances'      => [
        'provider'            => 'Aanbieder',
        'total_amount'        => 'Totaal uitgegeven bij',
        'highest_transaction' => 'Hoogste aankoopbedrag',
        'nr_transactions'     => 'Aantal transacties',
    ],

    'funds' => [
        'total'             => 'Totaal',

        // Overview funds
        'name'              => 'Fondsnaam',
        'total_top_up'      => 'Totaal gestort',
        'balance'           => 'Huidig saldo',
        'expenses'          => 'Uitgaven',
        'transactions'      => 'Transactiekosten',

        // Statistics funds
        'active'                        => 'Actief',
        'inactive'                      => 'Inactief',
        'left'                          => 'Restant',

        'amount_per_voucher'            => 'Per tegoed €',
        'average_per_voucher'           => 'Gem per tegoed €',
        'vouchers_amount'               => 'Totaal tegoeden €',
        'vouchers_count'                => 'Totaal tegoeden aantal',
        'vouchers_inactive_amount'      => 'Totaal tegoeden inactief €',
        'vouchers_inactive_percentage'  => 'Totaal tegoeden inactief %',
        'vouchers_inactive_count'       => 'Totaal tegoeden inactief aantal',
        'vouchers_active_amount'        => 'Totaal tegoeden actief €',
        'vouchers_active_percentage'    => 'Totaal percentage actief %',
        'vouchers_active_count'         => 'Totaalaantal actief',
        'total_spent_amount'            => 'Uitgaven €',
        'total_spent_percentage'        => 'Uitgaven %',
        'total_left'                    => 'Restant actieve tegoeden',
        'total_left_percentage'         => 'Totaal percentage restant',
    ]
];
