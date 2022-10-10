<?php

return [
    /**
     * By default, records are considered as valid 5 years after the validation
     */
    'record_validity_days' => env('RECORD_VALIDITY_DAYS', 365 * 5),
];
