<?php

namespace App\Http\Controllers\Api\Identity\Identity2FA;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Identity\Identity2FA\ActivateIdentity2FARequest;
use App\Http\Requests\Api\Identity\Identity2FA\AuthenticateIdentity2FARequest;
use App\Http\Requests\Api\Identity\Identity2FA\DeactivateIdentity2FARequest;
use App\Http\Requests\Api\Identity\Identity2FA\StoreIdentity2FARequest;
use App\Http\Requests\Api\Identity\Identity2FA\UpdateIdentity2FARequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Identity2FAResource;
use App\Http\Resources\Identity2FAStateResource;
use App\Models\Identity2FA;
use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use Throwable;

class Identity2FAController extends Controller
{
    use ThrottleWithMeta;

    /**
     * @param StoreIdentity2FARequest $request
     * @return Identity2FAStateResource
     */
    public function state(BaseFormRequest $request): Identity2FAStateResource
    {
        $this->authorize('state', [Identity2FA::class]);

        return Identity2FAStateResource::create($request->identity());
    }

    /**
     * @param UpdateIdentity2FARequest $request
     * @return Identity2FAStateResource
     */
    public function update(UpdateIdentity2FARequest $request): Identity2FAStateResource
    {
        $this->authorize('update', [Identity2FA::class]);

        $request->identity()->update($request->only([
            'auth_2fa_remember_ip',
        ]));

        return Identity2FAStateResource::create($request->identity()->fresh());
    }

    /**
     * @param StoreIdentity2FARequest $request
     * @return Identity2FAResource
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws Throwable
     */
    public function store(StoreIdentity2FARequest $request): Identity2FAResource
    {
        $this->authorize('store', [Identity2FA::class, $request->input('type')]);

        $identity = $request->identity();
        $auth2FASecret = $request->identity()->make2FASecret('Forus', $identity->email);
        $auth2FAProvider = Auth2FAProvider::where('type', $request->input('type'))->first();

        /** @var Identity2FA $identity2FA */
        $identity2FA = $identity->identity_2fa()->create(array_merge([
            'state' => Identity2FA::STATE_PENDING,
            'auth_2fa_provider_id' => $auth2FAProvider->id,
            'phone' => $request->input('phone'),
        ], match($auth2FAProvider->type) {
            $auth2FAProvider::TYPE_PHONE => [
                'phone' => $request->input('phone'),
            ],
            $auth2FAProvider::TYPE_AUTHENTICATOR => [
                'secret' => $auth2FASecret->getSecretKey(),
                'secret_url' => $auth2FASecret->getSecretUrl(),
            ],
        }));

        return (new Identity2FAResource($identity2FA))->additional([
            'code_sent' => $identity2FA->isTypePhone() && $identity2FA->sendCode(),
        ]);
    }

    /**
     * @param BaseFormRequest $request
     * @param Identity2FA $identity2FA
     * @return Identity2FAResource
     * @throws AuthorizationJsonException
     * @throws Throwable
     */
    public function resend(BaseFormRequest $request, Identity2FA $identity2FA): Identity2FAResource
    {
        $this->maxAttempts = Config::get('forus.auth_2fa.resend_throttle_attempts');
        $this->decayMinutes = Config::get('forus.auth_2fa.resend_throttle_decay');
        $this->throttleWithKey('to_many_attempts', $request, 'auth_2fa', 'resend');
        $this->authorize('resend', $identity2FA);

        return (new Identity2FAResource($identity2FA))->additional([
            'code_sent' => $identity2FA->isTypePhone() && $identity2FA->sendCode(),
        ]);
    }

    /**
     * @param ActivateIdentity2FARequest $request
     * @param Identity2FA $identity2FA
     * @return Identity2FAResource
     */
    public function activate(
        ActivateIdentity2FARequest $request,
        Identity2FA $identity2FA
    ): Identity2FAResource {
        $this->authorize('activate', $identity2FA);

        $code = $request->input('code');
        $proxy = $request->identityProxy();
        $provider = Auth2FAProvider::where('key', $request->input('key'))->first();

        $identity2FA->activate($code, $provider);
        $identity2FA->deactivateCode($code);

        if (!$proxy->identity_2fa_code && !$proxy->identity_2fa_uuid) {
            $request->identityProxy()->forceFill([
                'identity_2fa_code' => $code,
                'identity_2fa_uuid' => $identity2FA->uuid,
            ])->save();
        }

        return new Identity2FAResource($identity2FA);
    }

    /**
     * @param AuthenticateIdentity2FARequest $request
     * @param Identity2FA $identity2FA
     * @return JsonResponse
     */
    public function authenticate(
        AuthenticateIdentity2FARequest $request,
        Identity2FA $identity2FA,
    ): JsonResponse {
        $this->authorize('authenticate', $identity2FA);

        $request->identityProxy()->forceFill([
            'identity_2fa_uuid' => $identity2FA->uuid,
            'identity_2fa_code' => $request->input('code'),
        ])->save();

        $identity2FA->deactivateCode($request->input('code'));

        return new JsonResponse();
    }

    /**
     * @param DeactivateIdentity2FARequest $request
     * @param Identity2FA $identity2FA
     * @return JsonResponse
     */
    public function deactivate(
        DeactivateIdentity2FARequest $request,
        Identity2FA $identity2FA
    ): JsonResponse {
        $this->authorize('deactivate', $identity2FA);

        $identity2FA->deactivate($request->input('code'));
        $identity2FA->deactivateCode($request->input('code'));

        return new JsonResponse();
    }
}
