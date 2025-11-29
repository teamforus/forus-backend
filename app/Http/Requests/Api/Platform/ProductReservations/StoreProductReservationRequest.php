<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\ReservationField;
use App\Models\Voucher;
use App\Rules\ProductReservations\ProductIdToReservationRule;
use App\Rules\Vouchers\IdentityVoucherAddressRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class StoreProductReservationRequest extends BaseProductReservationFieldRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated() && Gate::allows('create', ProductReservation::class);
    }

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
     * @param Product|null $product
     * @return array
     */
    public function baseRules(?Product $product): array
    {
        return [
            'voucher_id' => [
                'required',
                new IdentityVoucherAddressRule($this->identity(), Voucher::TYPE_BUDGET),
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                new ProductIdToReservationRule($this->input('voucher_id'), true, true),
            ],
            ...$this->fieldsRules($product),
            ...$this->customFieldRules($product),
        ];
    }

    /**
     * @param Product|null $product
     * @return array
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
     * @return array
     */
    public function attributes(): array
    {
        $fields = Product::find($this->input('product_id'))?->getReservationFields();

        return [
            ...parent::attributes(),
            ...$fields?->reduce(fn (array $result, ReservationField $field) => [
                ...$result,
                "custom_fields.$field->id" => Arr::get($field->translateColumns($field->only([
                    'label',
                ])), 'label', $field->label || ''),
            ], []) ?: [],
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'birth_date.date_format' => trans('validation.date', [
                'attribute' => trans('validation.attributes.birth_date'),
            ]),
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
     * @return array[]
     */
    protected function fieldsRules(?Product $product): array
    {
        return [
            'first_name' => 'required|string|max:20',
            'last_name' => 'required|string|max:20',
            'user_note' => [
                $product->reservation_user_note_is_required ? 'required' : 'nullable',
                'string',
                'max:400',
            ],
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
        if (!$product?->reservation_fields_enabled) {
            return [];
        }

        $rules = [
            'custom_fields' => 'nullable|array',
        ];

        foreach ($product->getReservationFields() as $field) {
            $rules["custom_fields.$field->id"] = $this->getCustomFieldRules($field);
        }

        return $rules;
    }
}
