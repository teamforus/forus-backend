<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBIConnectionRequest;
use App\Models\BIConnection;
use Illuminate\Http\JsonResponse;

class BIConnectionController extends Controller
{
    /**
     * @param IndexBIConnectionRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(IndexBIConnectionRequest $request): JsonResponse
    {
        $connection = BIConnection::getConnectionFromRequest($request);

        return new JsonResponse($connection->getDataArray());
    }
}
