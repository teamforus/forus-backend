<?php

return [
    /**
     * Maximum amount per voucher a sponsor can create on vouchers page from
     * sponsor dashboard
     */
    'max_sponsor_voucher_amount' => 5000,

    /**
     * By default records are considered as valid 5 years after the validation
     */
    'record_validity_days' => env('RECORD_VALIDITY_DAYS', 365 * 5),
];
