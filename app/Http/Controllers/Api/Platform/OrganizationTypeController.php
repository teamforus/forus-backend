<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\OrganizationTypeResource;
use App\Http\Controllers\Controller;

class OrganizationTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return OrganizationTypeResource::collection(collect([[
                'key' => "provider",
                'value' => "Provider"
            ], [
                'key' => "sponsor",
                'value' => "Sponsor"
            ]
        ]));
    }
}
