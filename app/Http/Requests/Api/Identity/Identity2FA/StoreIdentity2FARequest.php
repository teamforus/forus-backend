<?php

namespace App\Http\Requests\Api\Identity\Identity2FA;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Identity2FA;
use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;
use Illuminate\Validation\Rule;

class StoreIdentity2FARequest extends BaseIdentity2FARequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (\Illuminate\Validation\Rules\Exists|\Illuminate\Validation\Rules\Unique|string)[][]
     *
     * @psalm-return array{type: list{'required', \Illuminate\Validation\Rules\Exists}, phone?: list{'required', 'numeric', 'digits_between:8,15', \Illuminate\Validation\Rules\Unique}}
     */
    public function rules(): array
    {
        $type = $this->input('type');
        $usedTypes = $this->identity()->auth_2fa_providers_active->pluck('type')->toArray();

        return array_merge([
            'type' => [
                'required',
                Rule::exists('auth_2fa_providers', 'type')->whereNotIn('type', $usedTypes),
            ],
        ], $type === Auth2FAProvider::TYPE_PHONE ? [
            'phone' => [
                'required',
                'numeric',
                'digits_between:8,15',
                Rule::unique('identity_2fa', 'phone')->where('state', Identity2FA::STATE_ACTIVE),
            ]
        ] : []);
    }
}
