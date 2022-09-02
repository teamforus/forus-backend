<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    /**
     * Get api availability state
     *
     * @return JsonResponse
     */
    public function getStatus(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            return new JsonResponse();
        } catch (\Throwable) {
            return new JsonResponse(null, 503);
        }
    }
}
