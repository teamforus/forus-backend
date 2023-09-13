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
        $product = Product::find($this->input('product_id'));

        return [
            'voucher_address' => [
                'required',
                new IdentityVoucherAddressRule($this->auth_address(), Voucher::TYPE_BUDGET),
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                new ProductIdToReservationRule($this->input('voucher_address'), true)
            ],
            ...$this->fieldsRules($product),
            ...$this->customFieldRules($product),
        ];
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
            'user_note' => 'nullable|string|max:400',
            'phone' => [
                $product->reservation_phone_is_required ? 'required' : 'nullable',
                'string',
                'max:50',
            ],
            'address' => [
                $product->reservation_address_is_required ? 'required' : 'nullable',
                'string',
                'max:100',
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
        return $product?->organization->reservation_fields->reduce(fn (array $result, $field) => [
            ...$result,
            "custom_fields.$field->id" => array_filter([
                $field->required ? 'required' : 'nullable',
                $field->type == $field::TYPE_NUMBER ? 'int' : 'string',
                $field->type == $field::TYPE_TEXT ? 'max:200' : null,
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
}
