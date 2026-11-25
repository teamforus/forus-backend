<?php

namespace App\Services\MeAppApiService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class MobileAnonymousResourceCollection extends AnonymousResourceCollection
{
    /**
     * @param Request $request
     * @param array $paginated
     * @param array $default
     * @return array
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return ['meta' => Arr::except($default['meta'], ['links', 'path'])];
    }
}
