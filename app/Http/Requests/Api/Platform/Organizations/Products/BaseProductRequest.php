<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Product;
use App\Rules\EanCodeRule;
use App\Rules\MediaUidRule;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
abstract class BaseProductRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'price_discount.required_if' => 'Het kortingsveld is verplicht.',
            'expire_at.after' => trans('validation.after', [
                'date' => trans('validation.attributes.today'),
            ]),
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'price_type.free' => 'gratis',
            'price_type.discount_fixed' => 'korting',
            'price_type.discount_percentage' => 'korting',
        ];
    }

    /**
     * @param string|null $priceType
     * @param Product|null $updatedProduct
     * @return array
     */
    protected function baseProductRules(?string $priceType, ?Product $updatedProduct): array
    {
        return [
            'name' => 'required|between:2,200',
            'description' => ['required', ...$this->markdownRules(5, 2500)],
            'alternative_text' => 'nullable|between:2,500',
            'price' => 'required_if:price_type,regular|numeric|min:.2',
            'media_uid' => ['nullable', new MediaUidRule('product_photo')],
            'price_type' => ['required', Rule::in($updatedProduct ? [$updatedProduct->price_type] : Product::PRICE_TYPES)],

            'price_discount' => match ($priceType) {
                'discount_fixed' => 'required|numeric|min:.1',
                'discount_percentage' => 'required|numeric|between:.1,100',
                default => [],
            },

            'expire_at' => 'nullable|date_format:Y-m-d|after:today',
            'product_category_id' => 'required|exists:product_categories,id',
            'sku' => 'nullable|string|alpha_num|max:200',
            'ean' => ['nullable', 'string', new EanCodeRule()],
            'qr_enabled' => 'nullable|boolean',
        ];
    }

    /**
     * @return string[]
     */
    protected function reservationRules(): array
    {
        $options = implode(',', Product::RESERVATION_FIELDS_PRODUCT);
        $policies = implode(',', Product::RESERVATION_POLICIES);

        $noteOptions = implode(',', Product::RESERVATION_NOTE_PRODUCT_OPTIONS);
        $noteCustomOption = Product::RESERVATION_FIELD_CUSTOM;

        $extraPaymentRules = $this->organization->canReceiveExtraPayments() ? [
            Rule::in(Product::RESERVATION_EXTRA_PAYMENT_OPTIONS),
        ] : [];

        return [
            'reservation_enabled' => 'nullable|boolean',
            'reservation_fields' => 'nullable|boolean',
            'reservation_policy' => "nullable|in:$policies",
            'reservation_phone' => "nullable|in:$options",
            'reservation_address' => "nullable|in:$options",
            'reservation_birth_date' => "nullable|in:$options",
            'reservation_extra_payments' => ['nullable', ...$extraPaymentRules],
            'reservation_note' => "nullable|in:$noteOptions",
            'reservation_note_text' => "required_if:reservation_note,$noteCustomOption|string|max:2000",
        ];
    }
}
