<?php

namespace App\Http\Requests\Api\Platform\Organizations\DCPOrders;

use App\Http\Requests\BaseFormRequest;
use App\Services\DCPService\DCPOrderPayloadBuilder;
use App\Services\DCPService\Models\DCPOrder;

class UpdateDCPOrderRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $order = $this->dcpOrder();
        $addressFields = DCPOrderPayloadBuilder::DELIVERY_ADDRESS_FIELDS;
        $tagFields = $order?->tagFields() ?: [];

        return [
            'delivery_address' => 'required|array:' . implode(',', $addressFields),
            ...array_combine(
                array_map(fn (string $key) => "delivery_address.$key", $addressFields),
                array_map(fn (string $key) => $this->addressFieldRules($key), $addressFields),
            ),
            'tags' => 'nullable|array:' . implode(',', array_column($tagFields, 'key')),
            ...array_combine(
                array_map(fn (array $field) => "tags.{$field['key']}", $tagFields),
                array_map(fn (array $field) => [
                    $field['required'] ? 'required' : 'nullable',
                    'string',
                    'max:' . ($field['max'] ?: 500),
                ], $tagFields),
            ),
        ];
    }

    /**
     * Show the keys of the fields as they are named at DCP.
     *
     * @return array
     */
    public function attributes(): array
    {
        $tagKeys = array_column($this->dcpOrder()?->tagFields() ?: [], 'key');
        $addressKeys = DCPOrderPayloadBuilder::DELIVERY_ADDRESS_FIELDS;

        return [
            ...array_combine(array_map(fn ($key) => "delivery_address.$key", $addressKeys), $addressKeys),
            ...array_combine(array_map(fn ($key) => "tags.$key", $tagKeys), $tagKeys),
        ];
    }

    /**
     * @param string $key
     * @return array
     */
    protected function addressFieldRules(string $key): array
    {
        return [
            match ($key) {
                // the full name is made of the first and last name when it is empty, the country defaults to NL
                'fullname' => 'required_without_all:delivery_address.firstname,delivery_address.lastname',
                'country', 'firstname', 'lastname' => 'nullable',
                default => in_array($key, DCPOrderPayloadBuilder::DELIVERY_ADDRESS_REQUIRED_FIELDS, true)
                    ? 'required'
                    : 'nullable',
            },
            'nullable',
            'string',
            'max:200',
        ];
    }

    /**
     * @return DCPOrder|null
     */
    protected function dcpOrder(): ?DCPOrder
    {
        $order = $this->route('dcp_order');

        return $order instanceof DCPOrder ? $order : null;
    }
}
