<?php

namespace App\Http\Requests\Api\Identity\Identity2FA;

use App\Exceptions\AuthorizationJsonException;
use App\Models\Identity2FA;
use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;
use Illuminate\Validation\Rule;

class StoreIdentity2FARequest extends BaseIdentity2FARequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws AuthorizationJsonException
     */
    public function authorize(): bool
    {
        $this->throttleRequest('store');

        return
            $this->identityProxy()->is2FAConfirmed() ||
            $this->identity()->identity_2fa_active()->doesntExist();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
                'string',
                'max:15',
                'starts_with:+',
                Rule::unique('identity_2fa', 'phone')->where('state', Identity2FA::STATE_ACTIVE),
            ]
        ] : []);
    }
}
