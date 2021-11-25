<?php

return [
    'skip_iban_numbers' => array_filter(explode(',', env('BUNQ_IBAN_SKIP_LIST', ''))),
];