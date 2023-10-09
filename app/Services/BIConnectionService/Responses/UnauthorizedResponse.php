<?php

namespace App\Services\BIConnectionService\Responses;

use Illuminate\Http\JsonResponse;

class UnauthorizedResponse extends JsonResponse
{
    public function __construct()
    {
        parent::__construct([
            'message' => 'invalid_api_key',
        ], 403);
    }
}