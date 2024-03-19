<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Rules\ProductReservations\ProductIdToReservationRule;
use App\Rules\Vouchers\IdentityVoucherAddressRule;
use Illuminate\Support\Facades\Gate;

class StoreProductReservationRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $product = $this->getProduct();

        return [
            ...$this->baseRules($product),
            ...$this->addressRules($product),
        ];
    }

    /**
     * @return Product|null
     */
    protected function getProduct(): ?Product
    {
        return Product::find($this->input('product_id'));
    }

    /**
     * @param Product|null $product
     *
     * @return (array|mixed)[]
     *
     * @psalm-return array{voucher_address: array|mixed, product_id: array|mixed,...}
     */
    public function baseRules(?Product $product): array
    {
        return [
            'voucher_address' => [
                'required',
                new IdentityVoucherAddressRule($this->auth_address(), Voucher::TYPE_BUDGET),
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                new ProductIdToReservationRule($this->input('voucher_address'), true, true),
            ],
            ...$this->fieldsRules($product),
            ...$this->customFieldRules($product),
        ];
    }

    /**
     * @param Product|null $product
     *
     * @return (mixed|string[])[]
     *
     * @psalm-return array{city: list{'nullable'|'required', 'city_name'}, street: list{'nullable'|'required', 'street_name'}, house_nr: list{'nullable'|'required', 'house_number'}, house_nr_addition: list{'nullable', 'house_addition'}, postal_code: list{'nullable'|'required', 'postcode'},...}
     */
    public function addressRules(?Product $product): array
    {
        $isPartiallyFilled = !empty(array_filter($this->only([
            'city', 'street', 'house_nr_addition', 'postal_code',
        ])));

        $required = $isPartiallyFilled || $product?->reservation_address_is_required;

        return array_merge(parent::rules(), [
            'city' => [$required ? 'required' : 'nullable', 'city_name'],
            'street' => [$required ? 'required' : 'nullable', 'street_name'],
            'house_nr' => [$required ? 'required' : 'nullable', 'house_number'],
            'house_nr_addition' => ['nullable', 'house_addition'],
            'postal_code' => [$required ? 'required' : 'nullable', 'postcode'],
        ]);
    }

    /**
     * @param Product|null $product
     *
     * @return (string|string[])[]
     *
     * @psalm-return array{first_name: 'required|string|max:20', last_name: 'required|string|max:20', user_note: 'nullable|string|max:400', phone: list{'nullable'|'required', 'string', 'max:50'}, birth_date: list{'nullable'|'required', 'date_format:Y-m-d', 'before:today'}}
     */
    protected function fieldsRules(?Product $product): array
    {
        return [
            'first_name' => 'required|string|max:20',
            'last_name' => 'required|string|max:20',
            'user_note' => 'nullable|string|max:400',
            'phone' => [
                $product->reservation_phone_is_required ? 'required' : 'nullable',
                'string',
                'max:50',
            ],
            'birth_date' => [
                $product->reservation_birth_date_is_required ? 'required' : 'nullable',
                'date_format:Y-m-d',
                'before:today',
            ],
        ];
    }

    /**
     * @param Product|null $product
     * @return array
     */
    private function customFieldRules(?Product $product): array
    {
        if (!$product->reservation_fields) {
            return [];
        }

        return $product?->organization->reservation_fields->reduce(fn (array $result, $field) => [
            ...$result,
            "custom_fields.$field->id" => array_filter([
                $field->required ? 'required' : 'nullable',
                $field->type === $field::TYPE_NUMBER ? 'int' : 'string',
                $field->type === $field::TYPE_TEXT ? 'max:200' : null,
            ])
        ], [
            'custom_fields' => 'nullable|array',
        ]);
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        $product = Product::find($this->input('product_id'));

        return $product?->organization->reservation_fields->reduce(fn (array $result, $field) => [
            ...$result,
            "custom_fields.$field->id" => $field->label,
        ], []) ?: [];
    }

    /**
     * @return (\Illuminate\Contracts\Translation\Translator|array|null|string)[]
     *
     * @psalm-return array{'birth_date.date_format': \Illuminate\Contracts\Translation\Translator|array|null|string}
     */
    public function messages(): array
    {
        return [
            'birth_date.date_format' => trans('validation.date', [
                'attribute' => trans('validation.attributes.birth_date')
            ])
        ];
    }
}
