<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExportBIConnectionRequest;
use App\Services\BIConnectionService\BIConnectionService;
use App\Services\BIConnectionService\Responses\UnauthorizedResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class BIConnectionController extends Controller
{
    /**
     * @param ExportBIConnectionRequest $request
     * @throws Throwable
     * @return JsonResponse
     */
    public function index(ExportBIConnectionRequest $request): JsonResponse
    {
        if ($connection = BIConnectionService::getBIConnectionFromRequest($request)) {
            return new JsonResponse($connection->getDataArray());
        }

        return new UnauthorizedResponse();
    }
}
