<?php

namespace App\Services\MeAppApiService\Http\Controllers\Shared;

use App\Http\Controllers\Api\IdentityController as LegacyIdentityController;
use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Services\MeAppApiService\Http\Resources\Shared\IdentityResource;

class IdentityController extends Controller
{
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
