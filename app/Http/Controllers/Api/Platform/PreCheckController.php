<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\PreChecks\IndexPreCheckRequest;
use App\Http\Resources\PreCheckResource;
use App\Models\Implementation;
use App\Models\PreCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PreCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexPreCheckRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexPreCheckRequest $request,
    ): AnonymousResourceCollection {
        return PreCheckResource::queryCollection(
            Implementation::active()->getPreChecks()->where('default', false), $request
        );
    }

    /**
     * Display the specified resource.
     *
     * @param PreCheck $preCheck
     * @return PreCheckResource
     */
    public function show(
        PreCheck $preCheck
    ): PreCheckResource {
        return PreCheckResource::create($preCheck);
    }

    /**
     * Calculate pre-check totals based on record inputs
     *
     * @param IndexPreCheckRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function calculateTotals(
        IndexPreCheckRequest $request,
    ): JsonResponse {
        return new JsonResponse(PreCheck::calculateTotals(
            $request->implementation(),
            $request->identity(),
            $request->only('pre_checks'))
        );
    }
}
