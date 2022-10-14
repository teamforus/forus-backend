<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\StoreFaqRequest;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

/**
 * Class FaqController
 * @package App\Http\Controllers\Api\Platform\Organizations
 */
class FaqController extends Controller
{
    /**
     * @param StoreFaqRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function storeValidate(
        StoreFaqRequest $request,
        Organization    $organization
    ): JsonResponse {
        $this->authorize('show', $organization);

        return response()->json([], $request->isAuthenticated() ? 200 : 403);
    }
}