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
}
