<?php

return [
    'bank_connection' => [
        'webshop' => [],
        'dashboard' => [
            'replaced' => '":bank" koppeling is vervangen',
            'disabled' => '":bank" koppeling is verbroken',
            'monetary_account_changed' => '":bank" account is vervangen voor :iban',
            'activated' => '":bank" koppeling is geactiveerd',
            // 'created' => ':bank connection has been created',
            // 'rejected' => ':bank connection has been rejected',
        ],
    ],
    'employees' => [
        'webshop' => [],
        'dashboard' => [
            'created' => ':email is toegevoegd als medewerker',
            'updated' => ':email is bijgewerkt',
            'deleted' => ':email is verwijderd',
        ],
    ],
    'fund' => [
        'webshop' => [],
        'dashboard' => [
            'vouchers_exported' => '<strong class="text-primary">:vouchers_count</strong> tegoeden geëxporteerd voor <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a> fonds.',
            'created' => 'Fonds <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a> is aangemaakt',
            'balance_low' => 'Budget dient opgehoogd te worden voor <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a>',
            'balance_supplied' => 'Budget is opgehoogd voor <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a>',
            'fund_started' => 'Fonds <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a> is gestart',
            'fund_ended' => 'Fonds <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a> is verlopen',
            'fund_expiring' => 'Fonds <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a> verloopt binnenkort',
            'archived' => 'Fonds <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a> is gearchiveerd',
            'unarchived' => 'Fonds <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a> is gedearchiveerd',
            'balance_updated_by_bank_connection' => 'Budget is gewijzigd voor <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a>',

            // 'provider_applied' => 'Provider :provider_name was applied for fund :fund_name',
            // 'provider_replied' => 'Provider :provider_name was replied for fund :fund_name',
            // 'provider_approved_products' => 'Provider :provider_name products was approved for fund :fund_name',
            // 'provider_approved_budget' => 'Provider :provider_name budget was approved for fund :fund_name',
            // 'provider_revoked_products' => 'Provider :provider_name products was revoked for fund :fund_name',
            // 'provider_revoked_budget' => 'Provider :provider_name budget was revoked for fund :fund_name',
            // 'fund_product_added' => 'Product :product_name was added in fund :fund_name',
            // 'fund_product_approved' => 'Product :product_name was approved in fund :fund_name',
            // 'fund_product_revoked' => 'Product :product_name was revoked in fund :fund_name',
            // 'fund_product_subsidy_removed' => 'Subsidy product :product_name was removed in fund :fund_name',
        ],
    ],
    'loggable' => [
        'fund' => 'Fonds: <a href=":dashboard_url/organizations/:sponsor_id/funds/:fund_id" class="text-primary text-medium">:fund_name</a>',
        'employees' => 'Medewerker: <a href=":dashboard_url/organizations/:organization_id/employees" class="text-primary text-medium">#:employee_id</a>',
        'voucher' => 'Tegoed: <a href=":dashboard_url/organizations/:sponsor_id/vouchers/:voucher_id" class="text-primary text-medium">#:voucher_number</a>',
        'bank_connection' => '<a href=":dashboard_url/organizations/:organization_id/bank-connections" class="text-primary text-medium">Bank koppelingen</a>',
    ],
    'physical_card_request' => [
        'created' => 'Pas is besteld',
    ],
    'voucher' => [
        'webshop' => [
            'created_budget' => 'Aangemaakt',
            'created_product' => 'Aangemaakt',
            'activated' => 'Geactiveerd',
            'assigned' => 'Toegekend',
            'deactivated' => 'Gedeactiveerd',
            'expired' => 'Verlopen',
            'expired_budget' => 'Verlopen',
            'expired_product' => 'Verlopen',
            'transaction' => 'Transactie',
            'transaction_subsidy' => 'Transactie',
            'transaction_budget' => 'Transactie',
            'transaction_product' => 'Transactie',
        ],
        'dashboard' => [
            'created_budget' => 'Tegoed #:number is aangemaakt',
            'created_product' => 'Tegoed #:number is aangemaakt',
            'activated' => 'Tegoed #:number is geactiveerd',
            'assigned' => 'Tegoed #:number is toegewezen',
            'deactivated' => 'Tegoed #:number is gedeactiveerd',
            'expired' => 'Tegoed #:number is verlopen',
            'expired_budget' => 'Tegoed #:number is verlopen',
            'expired_product' => 'Tegoed #:number is verlopen',
            'expiring_soon_budget' => 'Tegoed #:number verloopt binnenkort',
            'expiring_soon_product' => 'Tegoed #:number verloopt binnenkort',
            'limit_multiplier_changed' => 'Tegoed #:number aantal personen bijgewerkt',
            'transaction' => [
                'basic' => 'Tegoed #:number :transaction_type transactie aangemaakt',
                'complete' => 'Tegoed #:number :transaction_type transactie met het bedrag van :amount_locale',
            ],
            'transaction_subsidy' => 'Tegoed #:number transactie is aangemaakt',
            'transaction_budget' => 'Tegoed #:number transactie is aangemaakt',
            'transaction_product' => 'Tegoed #:number transactie is aangemaakt',
            'physical_card_requested' => 'Tegoed #:number plastic pas is besteld',
        ],
    ],
];
