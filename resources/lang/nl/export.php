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
        'products_provider_count' => 'Totaal aanbiedingen beheer door provider',
        'products_sponsor_count' => 'Totaal aanbiedingen beheert door sponsor',
        'products_active_count' => 'Totaal geaccepteerde aanbiedingen',
        'products_count' => 'Totaal aanbiedingen',
        'provider' => 'Aanbieder',
        'email' => 'E-mailadres',
        'phone' => 'Telefoonnummer',
        'categories' => 'Categoriën',
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
        'validator' => 'Validator',
        'created_at' => 'Indien datum',
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

    'funds' => [
        // Overview funds
        'name'      => 'Fondsnaam',
        'total'     => 'Totaal gestort',
        'current'   => 'Huidig saldo',
        'expenses'  => 'Uitgaven',
        'transactions' => 'Transactiekosten',

        // Statistics funds
        'active'    => 'Actief',
        'inactive'  => 'Inactief',
        'left'      => 'Restant',
        'amount_per_voucher'            => 'Per tegoed €',
        'average_per_voucher'           => 'Gem per tegoed €',
        'total_vouchers_amount'         => 'Totaal tegoeden €',
        'total_vouchers_count'          => 'Totaal tegoeden aantal',
        'vouchers_inactive_amount'      => 'Totaal tegoeden inactief €',
        'vouchers_inactive_percentage'  => 'Totaal tegoeden inactief %',
        'vouchers_inactive_count'       => 'Totaal tegoeden inactief aantal',
        'vouchers_active_amount'        => 'Totaal tegoeden actief',
        'total_spent_amount'            => 'Uitgaven €',
        'total_spent_percentage'        => 'Uitgaven %',
        'total_left'                    => 'Restant actieve tegoeden',
    ]
];
