<?php

namespace App\Http\Requests\OpenID;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Services\OpenIdService\Models\OpenIdFlow;
use App\Services\OpenIdService\Models\OpenIdSession;
use App\Services\OpenIdService\OpenIdService;
use Illuminate\Validation\Rule;

class StartOpenIdRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        $implementation = $this->implementation();
        $clientType = $this->client_type();
        $sessionRequest = $this->input('request') ?: OpenIdSession::REQUEST_AUTH;

        if (!OpenIdService::enabled()) {
            $this->deny(trans('requests.openid.not_enabled'));
        }

        if ($clientType !== Implementation::FRONTEND_WEBSHOP) {
            $this->deny(trans('requests.openid.invalid_client_type'));
        }

        if (!$implementation) {
            $this->deny(trans('requests.openid.invalid_implementation'));
        }

        if (!$implementation->openidAvailable()) {
            $this->deny(trans('requests.openid.not_enabled'));
        }

        if ($sessionRequest === OpenIdSession::REQUEST_FUND_REQUEST && !$this->isAuthenticated()) {
            $this->deny(trans('requests.openid.sign_in_first'));
        }

        if ($sessionRequest === OpenIdSession::REQUEST_AUTH && $this->identity()) {
            $this->deny(trans('requests.openid.already_authenticated'));
        }

        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $implementation = $this->implementation();

        $flowIds = $implementation
            ? $implementation->availableOpenIdFlows()->pluck('id')->all()
            : [];

        return [
            'request' => ['nullable', Rule::in(OpenIdSession::REQUEST_TYPES)],
            'flow_id' => ['required', 'integer', Rule::in($flowIds)],
            'fund_id' => [
                'nullable',
                'required_if:request,' . OpenIdSession::REQUEST_FUND_REQUEST,
                Rule::exists('funds', 'id')->whereIn(
                    'id',
                    Implementation::activeFundsQuery()->pluck('id')->toArray()
                ),
            ],
            'target' => 'nullable|string|max:200',
        ];
    }

    /**
     * @return OpenIdFlow|null
     */
    public function openidFlow(): ?OpenIdFlow
    {
        $flowId = $this->input('flow_id');

        if (is_numeric($flowId)) {
            return $this->implementation()?->availableOpenIdFlows()->firstWhere('id', (int) $flowId);
        }

        return null;
    }
}
