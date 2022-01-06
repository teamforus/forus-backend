<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Services\IConnectApiService\IConnectApiService;
use Illuminate\Http\JsonResponse;

/**
 * Class PersonBSNController
 * @package App\Http\Controllers\Api\Platform
 */
class PersonBSNController extends Controller
{
    /**
     * @param Organization $organization
     * @param $bsn
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function show(Organization $organization, $bsn): JsonResponse
    {
        $this->authorize('viewPersonBSNData', [
            FundRequest::class, $organization
        ]);

        $iconnect = new IConnectApiService($organization->person_bsn_api_id);

        $person = $iconnect->getPerson($bsn, ['parents', 'children', 'partners']);

        return $person
            ? response()->json(['data' => $person->toArray()])
            : response()->json(['message' => 'Not found'], 404);
    }
}