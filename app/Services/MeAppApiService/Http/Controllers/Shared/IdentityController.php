<?php

namespace App\Services\MeAppApiService\Http\Controllers\Shared;

use App\Http\Controllers\Api\IdentityController as LegacyIdentityController;
use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Services\MeAppApiService\Http\Requests\Shared\Identity\IdentityAuthorizationEmailRedirectRequest;
use App\Services\MeAppApiService\Http\Requests\Shared\Identity\IdentityStoreRequest;
use App\Services\MeAppApiService\Http\Resources\Shared\IdentityResource;
use App\Services\MeAppApiService\MeAppApiService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class IdentityController extends Controller
{
    /**
     * Start email authentication.
     *
     * @param IdentityStoreRequest $request
     * @return Response
     */
    public function store(IdentityStoreRequest $request): Response
    {
        $request->merge(['target' => null]);

        resolve(LegacyIdentityController::class)->store($request);

        return response()->noContent();
    }

    /**
     * Redirect from the email sign-in link.
     *
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $emailToken
     * @return View
     */
    public function emailTokenRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $emailToken
    ): View {
        $service = resolve(MeAppApiService::class);

        return $service->emailSignInView(
            $emailToken,
            $request->validated('client_type'),
            $request->validated('implementation_key'),
        );
    }

    /**
     * Redirect from the email confirmation link.
     *
     * @param IdentityAuthorizationEmailRedirectRequest $request
     * @param string $exchangeToken
     * @return View
     */
    public function emailConfirmationRedirect(
        IdentityAuthorizationEmailRedirectRequest $request,
        string $exchangeToken
    ): View {
        $service = resolve(MeAppApiService::class);

        return $service->emailConfirmationView(
            $exchangeToken,
            $request->validated('client_type'),
            $request->validated('implementation_key'),
        );
    }

    /**
     * Get your identity.
     *
     * @param BaseFormRequest $request
     * @return IdentityResource
     */
    public function show(BaseFormRequest $request): IdentityResource
    {
        $response = resolve(LegacyIdentityController::class)->getPublic($request);

        return new IdentityResource($response->resource);
    }
}
