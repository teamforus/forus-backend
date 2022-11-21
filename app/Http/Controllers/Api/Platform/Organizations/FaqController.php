<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\StoreFaqValidateRequest;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    /**
     * @param StoreFaqValidateRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function storeValidate(
        StoreFaqValidateRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize('show', $organization);

        return new JsonResponse([], $request->isAuthenticated() ? 200 : 403);
    }
}