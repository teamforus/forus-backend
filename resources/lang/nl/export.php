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

    'providers' => [
        'fund' => 'Fondsnaam',
        'implementation' => 'Website',
        'iban' => 'IBAN',
        'provider_last_activity' => 'Laatst actief',
        'products_provider_count' => 'Totaal aanbiedingen beheer door aanbieder',
        'products_sponsor_count' => 'Totaal aanbiedingen beheert door sponsor',
        'products_active_count' => 'Totaal geaccepteerde aanbiedingen',
        'products_count' => 'Aantal aanbiedingen',
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

    'fund_requests' => [
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
        'lead_time_days' => 'Doorlooptijd (dagen)',
        'lead_time_locale' => 'Doorlooptijd (leesbaar)',
        'records' => 'Gegevens',
    ],

    'prevalidations' => [
        'code' => 'Code',
        'used' => 'Geactiveerd',
        'used_no' => 'Nee',
        'used_yes' => 'Ja',
        'records' => 'Gegevens',
    ],

    'finances' => [
        'provider' => 'Aanbieder',
        'total_amount' => 'Totaal uitgegeven',
        'business_type' => 'Organisatie type',
        'nr_transactions' => 'Aantal transacties',
        'highest_transaction' => 'Hoogste aankoopbedrag',
    ],

    'funds' => [
        'total' => 'Totaal',

        // Overview funds
        'name' => 'Fondsnaam',
        'balance' => 'Huidig saldo',
        'expenses' => 'Uitgaven',
        'transactions' => 'Transactiekosten',
        'total_top_up' => 'Totaal gestort',

        // Statistics funds
        'left' => 'Restant',
        'active' => 'Actief',
        'inactive' => 'Inactief',

        // Budget vouchers
        'budget_children_count' => 'Aantal kinderen',
        'budget_amount_per_voucher' => 'Per tegoed €',
        'budget_average_per_voucher' => 'Gem per tegoed €',
        'budget_vouchers_amount' => 'Totaal tegoeden €',
        'budget_vouchers_count' => 'Totaal tegoeden aantal',
        'budget_vouchers_inactive_amount' => 'Totaal tegoeden inactief €',
        'budget_vouchers_inactive_percentage' => 'Totaal tegoeden inactief %',
        'budget_vouchers_inactive_count' => 'Totaal tegoeden inactief aantal',
        'budget_vouchers_active_amount' => 'Totaal tegoeden actief €',
        'budget_vouchers_active_percentage' => 'Totaal percentage actief %',
        'budget_vouchers_active_count' => 'Totaalaantal actief',
        'budget_total_spent_amount' => 'Uitgaven €',
        'budget_total_spent_percentage' => 'Uitgaven %',
        'budget_total_left_amount' => 'Restant actieve tegoeden',
        'budget_total_left_percentage' => 'Totaal percentage restant',
        'budget_vouchers_deactivated_amount' => 'Totaal gedeactiveerd €',
        'budget_vouchers_deactivated_count' => 'Totaal gedeactiveerd aantal',

        // Product vouchers
        'product_vouchers_amount' => 'Totaal aanbiedingsvouchers €',
        'product_vouchers_active_amount' => 'Totaal aanbiedingsvouchers actief €',
        'product_vouchers_inactive_amount' => 'Totaal aanbiedingsvouchers inactief €',
        'product_vouchers_deactivated_amount' => 'Totaal aanbiedingsvouchers gedeactiveerd €',

        // Payout vouchers
        'payout_vouchers_amount' => 'Totaal uitbetalingen €',
    ],

    'employees' => [
        'owner' => 'Eigenaar',
        'email' => 'E-mailadres',
        'branch_number' => 'Vestigingsnummer',
        'branch_name' => 'Vestigingsnaam',
        'branch_id' => 'VestigingID',
        'created_at' => 'Aangemaakt op',
        'updated_at' => 'Bijgewerkt op',
        'is_2fa_configured' => '2FA',
        'roles' => 'Rollen',
        'last_activity' => 'Laatste handeling',
    ],

    'fund_identities' => [
        'id' => 'ID',
        'email' => 'E-mail',
        'count_vouchers' => 'Totaal aantal vouchers',
        'count_vouchers_active' => 'Actieve vouchers',
        'count_vouchers_active_with_balance' => 'Actieve vouchers met saldo',
    ],

    'identity_profiles' => [
        'id' => 'ID',
        'given_name' => 'Voornaam',
        'family_name' => 'Achternaam',
        'email' => 'E-mail adres',
        'bsn' => 'BSN',
        'client_number' => 'Klantnummer',
        'birth_date' => 'Geboorte datum',
        'last_activity' => 'Laatste inlog',
        'city' => 'Woonplaats',
        'street' => 'Straatnaam',
        'house_number' => 'Huisnummer',
        'house_number_addition' => 'Huisnummer toevoeging',
        'postal_code' => 'Postcode',
        'municipality_name' => 'Gemeentenaam',
        'neighborhood_name' => 'Woonwijk',
    ],

    'reservations' => [
        'code' => 'Code',
        'product_name' => 'Aanbod',
        'amount' => 'Bedrag',
        'email' => 'E-mailadres',
        'first_name' => 'Voornaam',
        'last_name' => 'Achternaam',
        'user_note' => 'Opmerking',
        'phone' => 'Telefoonnummer',
        'address' => 'Adres',
        'birth_date' => 'Geboortedatum',
        'state' => 'Status',
        'created_at' => 'Aangemaakt op',
        'expire_at' => 'Verlopen op',
        'ean' => 'EAN',
        'sku' => 'SKU',
        'transaction_id' => 'Transactie ID',
    ],

    'reimbursements' => [
        'id' => 'ID',
        'code' => 'NR',
        'implementation_name' => 'Website',
        'fund_name' => 'Fonds',
        'amount' => 'Bedrag',
        'employee' => 'Medewerker',
        'email' => 'E-mail',
        'bsn' => 'BSN',
        'iban' => 'IBAN',
        'iban_name' => 'Tenaamstelling',
        'provider_name' => 'Aanbieder',
        'category' => 'Categorie',
        'title' => 'Titel',
        'description' => 'Toelichting',
        'files_count' => 'Bon of factuur',
        'lead_time' => 'Afhandeltijd',
        'submitted_at' => 'Aangemaakt op',
        'resolved_at' => 'Behandeld op',
        'expired' => 'Verlopen',
        'state' => 'Status',
    ],

    'vouchers' => [
        'number' => 'Nummer',
        'granted' => 'Toegekend',
        'identity_email' => 'E-mailadres',
        'in_use' => 'In gebruik',
        'has_payouts' => 'Heeft uitbetalingen',
        'has_transactions' => 'Transactie gemaakt',
        'has_reservations' => 'Reservering gemaakt',
        'state' => 'Status',
        'amount' => 'Bedrag',
        'amount_available' => 'Huidig bedrag',
        'in_use_date' => 'In gebruik datum',
        'activation_code' => 'Activatiecode',
        'fund_name' => 'Fondsnaam',
        'implementation_name' => 'Website',
        'reference_bsn' => 'BSN (door medewerker)',
        'client_uid' => 'Uniek nummer',
        'created_at' => 'Aangemaakt op',
        'identity_bsn' => 'BSN (DigiD)',
        'source' => 'Aangemaakt door',
        'product_name' => 'Aanbod naam',
        'note' => 'Notitie',
        'expire_at' => 'Verlopen op',
    ],

    'voucher_transaction_bulks' => [
        'id' => 'ID',
        'state' => 'Status',
        'amount' => 'Bedrag',
        'quantity' => 'Aantal',
        'bank_name' => 'Bank naam',
        'date_transaction' => 'Datum transactie',
        'state-values' => [
            'draft' => 'In voorbereiding',
            'error' => 'Mislukt',
            'pending' => 'In afwachting',
            'accepted' => 'Geaccepteerd',
            'rejected' => 'Geweigerd',
        ],
    ],

    'voucher_transactions' => [
        'id' => 'ID',
        'amount' => 'Bedrag',
        'method' => 'Betaalmethode(s)',
        'branch_id' => 'Vestiging ID',
        'branch_name' => 'Vestigingsnaam',
        'branch_number' => 'Vestigingsnummer',
        'amount_extra' => 'Extra betaling',
        'date_transaction' => 'Datum transactie',
        'date_payment' => 'Datum betaling',
        'fund_name' => 'Fonds',
        'product_name' => 'Aanbod naam',
        'provider' => 'Aanbieder',
        'state' => 'Status',
        'amount_extra_cash' => 'Gevraagde bijbetaling',
        'date_non_cancelable' => 'Definitieve transactie',
        'bulk_status_locale' => 'In de wachtrij (dagen)',
        'product_id' => 'Aanbod ID',
        'notes_provider' => 'Notities',
        'reservation_code' => 'Reservering nummer',
        'state-values' => [
            'success' => 'voltooid',
            'pending' => 'in afwachting',
            'canceled' => 'geannuleerd',
        ],
    ],

    'physical_card_requests' => [
        'address' => 'ADRES',
        'house' => 'HUISNUMMER',
        'house_addition' => 'HUISNR_TOEVOEGING',
        'postcode' => 'POSTCODE',
        'city' => 'PLAATS',
    ],

    'event_logs' => [
        'created_at' => 'Tijd & Datum',
        'loggable' => 'Onderwerp',
        'event' => 'Activiteit',
        'identity_email' => 'Door (Medewerker)',
        'note' => 'Notitie',
    ],
];
