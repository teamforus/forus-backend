<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\TranslationStats\TranslationStatsRequest;
use App\Models\Organization;
use App\Services\TranslationService\Models\TranslationValue;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class TranslationStatsController extends Controller
{
    /**
     * @param TranslationStatsRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function stats(
        TranslationStatsRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('update', $organization);

        return new JsonResponse([
            'data' => TranslationValue::getUsage(
                $organization->id,
                Carbon::parse($request->get('from', now()->startOfMonth()->format('Y-m-d'))),
                Carbon::parse($request->get('to', now()->endOfMonth()->format('Y-m-d'))),
            ),
            'current_month' => TranslationValue::getUsage(
                $organization->id,
                now()->startOfMonth(),
                now()->endOfMonth(),
            ),
        ]);
    }
}
