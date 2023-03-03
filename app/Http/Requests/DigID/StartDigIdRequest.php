<?php

namespace App\Http\Requests\DigID;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use Illuminate\Validation\Rule;

class StartDigIdRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize(): bool
    {
        $implementation = $this->implementation();
        $clientType = $this->client_type();

        $isAuthenticated = $this->isAuthenticated();
        $isAuthRequest = $this->input('request') === 'auth';

        if (!$clientType || !in_array($clientType, Implementation::FRONTEND_KEYS, true)) {
            $this->deny("Invalid client type.");
        }

        if (!$implementation) {
            $this->deny("Invalid implementation.");
        }

        if (!$implementation->digidEnabled()) {
            $this->deny("DigId not enabled for this implementation.");
        }

        if (!$isAuthRequest && !$isAuthenticated) {
            $this->deny("Please sign-in first.");
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $redirectTypes = [
            'fund_request', 'auth'
        ];

        return [
            'request' => 'required|in:' . implode(',', $redirectTypes),
            'fund_id' => [
                'required_if:redirect_type,fund_request',
                Rule::exists('funds', 'id')->whereIn(
                    'id',
                    Implementation::activeFundsQuery()->pluck('id')->toArray()
                )
            ]
        ];
    }
}
