<?php

return [
    'fund_calc' => env('EMAIL_FOR_FUND_CALC', false),
    'fund_created' => env('EMAIL_FOR_FUND_CREATED', false),
    'identity_destroy' => env('EMAIL_FOR_IDENTITY_DESTROY', false),
    'transaction_verify' => env('EMAIL_FOR_TRANSACTION_VERIFY', env('EMAIL_FOR_FUND_CREATED', false)),
];