<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Services\WalletService\Models\WalletFlow;
use App\Services\WalletService\WalletService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImplementationWalletsRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $flowKeys = WalletFlow::configuredForProvider(WalletService::PROVIDER_VERID)->pluck('key')->all();

        return [
            'wallet_enabled' => 'required|boolean',
            'wallet_flow_keys' => ['present', 'array'],
            'wallet_flow_keys.*' => ['string', Rule::in($flowKeys)],
        ];
    }
}
