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
            'approved_partly' => 'Aanvulling gevraagd',
        ],
        'validator'         => 'Validator',
        'created_at'        => 'Indien datum',
        'resolved_at'       => 'Oplosdatum',
        'lead_time_days'    => 'Doorlooptijd (dagen)',
        'lead_time_locale'  => 'Doorlooptijd (leesbaar)',
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
            'canceled' => 'geannuleerd',
        ]
    ],

    'voucher_transactions_bulks' => [
        'state-values' => [
            'draft'    => 'Draft',
            'error'    => 'Error',
            'pending'  => 'In afwachting',
            'accepted' => 'Geaccepteerd',
            'rejected' => 'Geweigerd',
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

        // Budget vouchers
        'budget_amount_per_voucher'             => 'Per tegoed €',
        'budget_average_per_voucher'            => 'Gem per tegoed €',
        'budget_vouchers_amount'                => 'Totaal tegoeden €',
        'budget_vouchers_count'                 => 'Totaal tegoeden aantal',
        'budget_vouchers_inactive_amount'       => 'Totaal tegoeden inactief €',
        'budget_vouchers_inactive_percentage'   => 'Totaal tegoeden inactief %',
        'budget_vouchers_inactive_count'        => 'Totaal tegoeden inactief aantal',
        'budget_vouchers_active_amount'         => 'Totaal tegoeden actief €',
        'budget_vouchers_active_percentage'     => 'Totaal percentage actief %',
        'budget_vouchers_active_count'          => 'Totaalaantal actief',
        'budget_total_spent_amount'             => 'Uitgaven €',
        'budget_total_spent_percentage'         => 'Uitgaven %',
        'budget_total_left'                     => 'Restant actieve tegoeden',
        'budget_total_left_percentage'          => 'Totaal percentage restant',
        'budget_deactivated_amount'             => 'Totaal gedeactiveerd €',
        'budget_deactivated_count'              => 'Totaal gedeactiveerd aantal',

        // Product vouchers
        'product_vouchers_amount'               => 'Totaal aanbiedingsvouchers €',
        'product_vouchers_active_amount'        => 'Totaal aanbiedingsvouchers actief €',
        'product_vouchers_inactive_amount'      => 'Totaal aanbiedingsvouchers inactief €',
        'product_vouchers_deactivated_amount'   => 'Totaal aanbiedingsvouchers gedeactiveerd €',
    ]
];
