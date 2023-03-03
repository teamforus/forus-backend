<?php

namespace App\Http\Requests\Api\Platform\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Rules\ProductReservations\ProductIdToReservationRule;
use App\Rules\Vouchers\IdentityVoucherAddressRule;
use Illuminate\Support\Facades\Gate;

/**
 * Class StoreProductReservationRequest
 * @package App\Http\Requests\Api\Platform\Vouchers
 */
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
                $product->reservation_requester_birth_date_is_required ? 'required' : 'nullable',
                'date_format:Y-m-d',
                'before:today',
            ],
            'voucher_address' => [
                'required',
                new IdentityVoucherAddressRule($this->auth_address(), Voucher::TYPE_BUDGET),
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                new ProductIdToReservationRule($this->input('voucher_address'))
            ],
            'contact_information_rule' => empty($this->identity()->email) ? [
                'required'
            ] : [],
        ];
    }
}
