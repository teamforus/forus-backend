<?php

namespace App\Services\MeAppApiService\Documentation\Schemas;

use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Illuminate\Support\Facades\Config;

class PaymentInputSchema extends ObjectType
{
    /**
     * @return array
     */
    public function serializeAs31(): array
    {
        $note = ['type' => ['string', 'null'], 'minLength' => 2, 'maxLength' => 255];
        $id = ['type' => ['integer', 'string'], 'description' => 'An existing ID permitted for this payment.'];

        $amount = [
            'anyOf' => [
                ['type' => 'number', 'minimum' => 0.02],
                ['type' => 'string', 'pattern' => '^[+-]?(?:[0-9]+(?:\\.[0-9]*)?|\\.[0-9]+)(?:[eE][+-]?[0-9]+)?$'],
            ],
            'description' => 'Number or numeric string from 0.02 up to the available voucher balance.',
        ];

        $extraCash = $amount;
        $extraCash['anyOf'][] = ['type' => 'null'];
        $extraCash['anyOf'][0]['maximum'] = (float) Config::get('forus.transactions.max_amount_extra_cash');
        $extraCash['description'] = 'Optional extra cash: null, a number, or a numeric string from 0.02 to '
            . number_format(Config::get('forus.transactions.max_amount_extra_cash'), 2, '.', '')
            . '.';

        $common = ['note' => $note, 'amount_extra_cash' => $extraCash, 'organization_id' => $id];

        return [
            ...$this->extensionPropertiesToArray(),
            'type' => 'object',
            'description' => 'Use the variant matching the voucher in the URL. '
                . 'Budget payments require `organization_id` and either `amount` or `product_id`. '
                . 'For product vouchers, send only an optional `note`. '
                . 'Never send both `amount` and `product_id`, even as null. Extra fields are not generally rejected, '
                . 'but `product_id` also affects authorization.',
            'not' => ['required' => ['amount', 'product_id']],
            'anyOf' => [
                [
                    'title' => 'Budget amount payment',
                    'type' => 'object',
                    'description' => 'For budget vouchers. Requires permission to scan the voucher '
                        . 'for the selected organization.',
                    'properties' => [...$common, 'amount' => $amount],
                    'required' => ['amount', 'organization_id'],
                    'not' => ['required' => ['product_id']],
                    'examples' => [['amount' => 2.5, 'organization_id' => 1, 'note' => 'Example payment']],
                ],
                [
                    'title' => 'Budget product payment',
                    'type' => 'object',
                    'description' => 'For budget vouchers. The product must be available for the voucher '
                        . 'and selected organization. The server determines the payment amount.',
                    'properties' => [...$common, 'product_id' => $id],
                    'required' => ['product_id', 'organization_id'],
                    'not' => ['required' => ['amount']],
                    'examples' => [['product_id' => 1, 'organization_id' => 1]],
                ],
                [
                    'title' => 'Product voucher payment',
                    'type' => 'object',
                    'description' => 'For product vouchers, including reservations. Only `note` is validated. '
                        . 'Product and amount come from the voucher.',
                    'properties' => ['note' => $note],
                    'examples' => [(object) [], ['note' => 'Example redemption']],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function serializeAs32(): array
    {
        return $this->serializeAs31();
    }
}
