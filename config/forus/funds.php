<?php

return [
    /**
     * Maximum amount per voucher a sponsor can create on vouchers page from
     * sponsor dashboard
     */
    'max_sponsor_voucher_amount'      => 1000,
    'disable_voucher_requests_for_funds' => array_merge(
        explode(',', env('DISABLE_VOUCHER_REQUESTS_FOR_FUNDS', '')), [

        ]
    )
];
