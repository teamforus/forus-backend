<?php

namespace App\Http\Requests\Api\Platform\IdentityProviders;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Config;

class StartIdentityProviderLoginRequest extends BaseFormRequest
{
    /**
     * @return Response
     */
    public function authorize(): Response
    {
        if (!Config::get('identity_providers.enabled')) {
            return Response::deny(__('policies.identity_providers.sign_in_disabled'));
        }

        if (!in_array($this->client_type(), [
            Implementation::FRONTEND_SPONSOR_DASHBOARD,
            Implementation::FRONTEND_WEBSHOP,
        ], true)) {
            return Response::deny(__('policies.identity_providers.client_not_supported'));
        }

        return Response::allow();
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'target' => 'nullable|string|alpha_dash|max:200',
        ];
    }
}
