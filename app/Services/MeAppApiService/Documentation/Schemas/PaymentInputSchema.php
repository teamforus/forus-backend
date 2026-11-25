<?php

namespace App\Services\MeAppApiService\Documentation\Schemas;

use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Illuminate\Support\Facades\Config;

class PaymentInputSchema extends ObjectType
{
    /**
     * Register stable names for payment variant descriptions.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttribute('description_variants', [
            'budget_amount' => 0,
            'budget_product' => 1,
            'product_voucher' => 2,
        ]);
    }

    /**
     * @return array
     */
    public function serializeAs31(): array
    {
        $note = ['type' => ['string', 'null'], 'minLength' => 2, 'maxLength' => 255];
        $id = ['type' => ['integer', 'string']];

        $amount = [
            'anyOf' => [
                ['type' => 'number', 'minimum' => 0.02],
                ['type' => 'string', 'pattern' => '^[+-]?(?:[0-9]+(?:\\.[0-9]*)?|\\.[0-9]+)(?:[eE][+-]?[0-9]+)?$'],
            ],
        ];

        $extraCash = $amount;
        $extraCash['anyOf'][] = ['type' => 'null'];
        $extraCash['anyOf'][0]['maximum'] = (float) Config::get('forus.transactions.max_amount_extra_cash');

        $common = ['note' => $note, 'amount_extra_cash' => $extraCash, 'organization_id' => $id];

        return [
            ...$this->extensionPropertiesToArray(),
            'type' => 'object',
            'not' => ['required' => ['amount', 'product_id']],
            'anyOf' => [
                [
                    'type' => 'object',
                    'properties' => [...$common, 'amount' => $amount],
                    'required' => ['amount', 'organization_id'],
                    'not' => ['required' => ['product_id']],
                ],
                [
                    'type' => 'object',
                    'properties' => [...$common, 'product_id' => $id],
                    'required' => ['product_id', 'organization_id'],
                    'not' => ['required' => ['amount']],
                ],
                [
                    'type' => 'object',
                    'properties' => ['note' => $note],
                ],
            ],
        ];
    }
}
