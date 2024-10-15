<?php

return [
    'hard_limit' => env('TRANSACTIONS_HARD_LIMIT', 5),
    'soft_limit' => env('TRANSACTIONS_SOFT_LIMIT', 15),
    'max_amount_extra_cash' => env('TRANSACTIONS_MAX_AMOUNT_EXTRA_CASH', 500),
];