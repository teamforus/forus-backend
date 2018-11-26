<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\OfficeResource;
use App\Http\Controllers\Controller;
use App\Models\Implementation;
use App\Models\Office;
use Illuminate\Database\Query\Builder;

class OfficesController extends Controller
{
    /**
     * Display a listing of all available offices.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return OfficeResource::collection(Office::query()->whereIn(
            'organization_id',
            function (Builder $query) {
                $query->from('fund_providers')->select([
                    'organization_id'
                ])->where([
                    'state' => 'approved'
                ])->whereIn(
                    'fund_id', Implementation::activeFunds()->pluck('id')
                );
            }
        )->get());
    }

    /**
     * Display the specified resource.
     *
     * @param Office $office
     * @return OfficeResource
     */
    public function show(Office $office)
    {
        return new OfficeResource($office);
    }
}
