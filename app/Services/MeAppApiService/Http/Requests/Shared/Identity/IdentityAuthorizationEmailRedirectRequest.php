<?php

namespace App\Services\MeAppApiService\Http\Requests\Shared\Identity;

use App\Http\Requests\Api\IdentityAuthorizationEmailRedirectRequest as LegacyIdentityAuthorizationEmailRedirectRequest;
use App\Services\MeAppApiService\MeAppApiService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class IdentityAuthorizationEmailRedirectRequest extends LegacyIdentityAuthorizationEmailRedirectRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        $clientTypes = $this->routeIs('mobile.requester.*')
            ? Config::get('me-app-api.requester.client_types')
            : Config::get('me-app-api.provider.client_types');

        $clientKeys = $this->routeIs('mobile.requester.*')
            ? Config::get('me-app-api.requester.client_keys')
            : Config::get('me-app-api.provider.client_keys');

        return [
            'client_type' => ['required', Rule::in($clientTypes)],
            'implementation_key' => [
                'bail',
                'required',
                'string',
                Rule::in($clientKeys),
                'exists:implementations,key',
            ],
        ];
    }

    /**
     * @param Validator $validator
     * @throws HttpResponseException
     * @return void
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(resolve(MeAppApiService::class)->authErrorResponse(
            'This sign-in link is invalid',
            'Open the app and request a new sign-in email.',
            422,
        ));
    }
}
