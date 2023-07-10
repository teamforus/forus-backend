<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExportBIConnectionRequest;
use App\Services\BIConnectionService\BIConnection;
use App\Services\BIConnectionService\Responses\UnauthorizedResponse;
use Illuminate\Http\JsonResponse;

class BIConnectionController extends Controller
{
    /**
     * @param ExportBIConnectionRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(ExportBIConnectionRequest $request): JsonResponse
    {
        if ($connection = BIConnection::getBIConnectionFromRequest($request)) {
            return new JsonResponse($connection->getDataArray());
        }

        return new UnauthorizedResponse();
    }
}
