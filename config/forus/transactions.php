<?php

return [
    'hard_limit' => env('TRANSACTIONS_HARD_LIMIT', 5),
    'soft_limit' => env('TRANSACTIONS_SOFT_LIMIT', 15),
];