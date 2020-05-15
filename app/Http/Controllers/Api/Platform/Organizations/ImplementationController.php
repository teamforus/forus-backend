<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImplementationResource;
use App\Models\Implementation;
use App\Models\Organization;
use App\Scopes\Builders\ImplementationQuery;
use Illuminate\Http\Request;

class ImplementationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);

        $query = Implementation::query();
        if ($q = $request->input('q')) {
            $query = ImplementationQuery::whereQueryFilter($query, $q);
        }

        return ImplementationResource::collection(
            ImplementationQuery::whereOrganizationIdFilter(
                $query, $organization->id
            )->paginate($request->input('per_page'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Implementation $implementation
    ) {
        $this->authorize('show', $organization);

        return new ImplementationResource($implementation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        Request $request,
        Organization $organization,
        Implementation $implementation
    ) {
        $this->authorize('show', $organization);

        $implementation->update($request->all());

        return new ImplementationResource($implementation);
    }
}
