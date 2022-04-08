<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Fund;
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
     * @param Fund $fund
     * @param $bsn
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Organization $organization, Fund $fund, $bsn): JsonResponse
    {
        $this->authorize('viewPersonBSNData', [
            FundRequest::class, $organization, $fund
        ]);

        $person = $fund->getIConnect()->getPerson($bsn, [
            'parents', 'children', 'partners',
        ]);

        return new JsonResponse([
            'data' => $person ? $person->toArray() : null,
        ]);
    }
}