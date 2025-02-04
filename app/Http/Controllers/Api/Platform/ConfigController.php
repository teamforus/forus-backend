<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Implementation;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * @param string $type
     * @return JsonResponse
     */
    public function getConfig(string $type): JsonResponse
    {
        return new JsonResponse(Implementation::platformConfig($type));
    }
}
