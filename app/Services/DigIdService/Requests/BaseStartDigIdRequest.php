<?php

namespace App\Services\DigIdService\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Objects\DigIdSessionData;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

abstract class BaseStartDigIdRequest extends BaseFormRequest
{
    /**
     * @return Organization
     */
    abstract public function sessionOrganization(): Organization;

    /**
     * @return DigIdSessionData
     */
    abstract public function sessionData(): DigIdSessionData;

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::forUser($this->identity())->authorize('start', [
            DigIdSession::class,
            $this->implementation(),
            $this->client_type(),
            $this->input('request') === DigIdSession::SESSION_REQUEST_AUTH,
        ])->allowed();
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'request' => 'required|in:' . implode(',', DigIdSession::SESSION_REQUESTS),
            'browser_challenge' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'fund_id' => [
                'required_if:request,' . DigIdSession::SESSION_REQUEST_FUND_REQUEST,
                Rule::exists('funds', 'id')->whereIn(
                    'id',
                    Implementation::activeFundsQuery()->pluck('id')->toArray()
                ),
            ],
        ];
    }

    /**
     * @param Organization $organization
     * @param string $connectionType
     * @param string|null $dvEntityId
     * @param string|null $serviceUuid
     * @return DigIdSessionData
     */
    protected function makeSessionData(
        Organization $organization,
        string $connectionType,
        ?string $dvEntityId = null,
        ?string $serviceUuid = null,
    ): DigIdSessionData {
        return new DigIdSessionData(
            implementationId: $this->implementation()->id,
            organizationId: $organization->id,
            connectionType: $connectionType,
            clientType: $this->client_type(),
            identityAddress: $this->auth_address(),
            sessionRequest: $this->input('request'),
            sessionFinalUrl: $this->sessionFinalUrl(),
            browserChallenge: $this->input('browser_challenge'),
            fundId: $this->input('request') === DigIdSession::SESSION_REQUEST_FUND_REQUEST
                ? (int) $this->input('fund_id')
                : null,
            dvEntityId: $dvEntityId,
            serviceUuid: $serviceUuid,
        );
    }

    /**
     * @return string
     */
    protected function sessionFinalUrl(): string
    {
        if ($this->input('request') === DigIdSession::SESSION_REQUEST_FUND_REQUEST) {
            $fund = Fund::findOrFail($this->input('fund_id'));

            return $fund->urlWebshop(sprintf('/fondsen/%s/activeer', $fund->id));
        }

        return $this->implementation()->urlFrontend($this->client_type());
    }
}
