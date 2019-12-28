<?php

namespace App\Http\Requests\Api\Platform\Funds\ProviderInvitations;

use App\Models\Fund;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreFundProviderInvitationsRequest
 * @property Fund|null $fund
 * @package App\Http\Requests\Api\Platform\Funds\ProviderInvitations
 */
class StoreFundProviderInvitationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $fund = $this->fund;

        $fromFunds = $fund ? $fund->organization->funds()->whereKeyNot(
            $fund->id
        )->pluck('id')->toArray() : [];

        return [
            'fund_id' => [
                'required',
                Rule::in($fromFunds)
            ]
        ];
    }
}
