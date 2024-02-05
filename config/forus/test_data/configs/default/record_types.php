<?php

return [[
    'key' => "children_nth",
    'name' => "Number of children",
    'type' => "number",
    'system' => false,
    'vouchers' => false,
    'criteria' => true,
], [
    'key' => "income_level",
    'name' => "Income level",
    'type' => "number",
    'system' => false,
    'vouchers' => false,
    'criteria' => true,
], [
    'key' => 'iban',
    'name' => 'IBAN',
    'type' => 'iban',
    'criteria' => true,
], [
    'key' => 'municipality',
    'name' => 'Municipality',
    'type' => 'select',
    'criteria' => true,
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
]];