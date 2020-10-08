<?php

namespace App\Http\Requests\Api\Platform\Funds;

use App\Models\Fund;
use App\Services\BunqService\BunqService;
use Illuminate\Foundation\Http\FormRequest;

class StoreIdealBunqMeRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return request()->route('fund_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     * @throws \Exception
     */
    public function rules(): array
    {
        /** @var Fund $fund */
        $fund = request()->route('fund_id');

        $issuers = BunqService::getIdealIssuers(
            $fund->fund_config->bunq_sandbox
        )->pluck('bic');

        return [
            'description'   => 'nullable|string|max:400',
            'amount'        => 'required|numeric|min:.1',
            'issuer'        => 'nullable|in:' . $issuers->implode(','),
        ];
    }
}
