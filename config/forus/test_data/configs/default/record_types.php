<?php

return [[
    'key' => "children_nth",
    'name' => "Number of children",
    'type' => "number",
    'control_type' => 'step',
    'system' => false,
    'vouchers' => false,
    'criteria' => true,
], [
    'key' => "income_level",
    'name' => "Income level",
    'type' => "number",
    'control_type' => 'number',
    'system' => false,
    'vouchers' => false,
    'criteria' => true,
], [
    'key' => 'iban',
    'name' => 'IBAN',
    'type' => 'iban',
    'criteria' => true,
], [
    'key' => 'iban_name',
    'name' => 'IBAN Name',
    'type' => 'string',
    'criteria' => true,
], [
    'key' => 'municipality',
    'name' => 'Municipality',
    'type' => 'select',
    'criteria' => true,
    'control_type' => 'select',
    'options' => [
        ['268', 'Nijmegen'],
        ['1699', 'Noordenveld'],
        ['1969', 'Westerkwartier'],
        ['1979', 'Eemsdelta'],
    ],
], [
    'key' => 'single_parent',
    'name' => 'Civil status',
    'type' => 'bool',
    'criteria' => true,
    'control_type' => 'checkbox',
]];