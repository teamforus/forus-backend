<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\FundRequest;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

/**
 * Class PersonBSNController
 * @package App\Http\Controllers\Api\Platform
 * @noinspection PhpUnused
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
        $this->authorize('viewPersonBSNData', [FundRequest::class, $organization]);

        $person = $organization->getIConnect()->getPerson($bsn, [
            'parents', 'children', 'partners',
        ]);

        return new JsonResponse([
            'data' => $person ? $person->toArray() : null,
        ]);
    }
}