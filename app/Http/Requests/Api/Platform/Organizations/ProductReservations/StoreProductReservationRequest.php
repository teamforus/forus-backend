<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\ProductReservations\ProductIdToReservationRule;
use App\Scopes\Builders\OrganizationQuery;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Class AcceptProductReservationRequest
 * @property Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\ProductReservations
 */
class StoreProductReservationRequest extends BaseFormRequest
{
    use ThrottleWithMeta;

    protected $maxAttempts = 100;
    protected $decayMinutes = 180;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((ProductIdToReservationRule|null|string)[]|string)[]
     *
     * @psalm-return array{number: list{'required', 'exists:physical_cards,code', 'in:'|null}, product_id: list{'required', 'exists:products,id', ProductIdToReservationRule}, note: 'nullable|string|max:2000'}
     */
    public function rules(): array
    {
        $voucher = Voucher::findByAddressOrPhysicalCard($this->input('number'));

        $sponsorIsValid = OrganizationQuery::whereHasPermissionToScanVoucher(
            Organization::query(),
            $this->auth_address(),
            $voucher
        )->where('organizations.id', $this->organization->id)->exists();

        $addressIsValid = Gate::allows('useAsProvider', $voucher);

        return [
            'number' => [
                'required',
                'exists:physical_cards,code',
                $sponsorIsValid && $addressIsValid ? null : 'in:'
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                new ProductIdToReservationRule($this->input('number')),
            ],
            'note' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get the throttle key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        return Str::lower(($this->throttleKeyPrefix ?: '') . $this->organization->id);
    }
}
