<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\UpdateImplementationPageRequest;
use App\Http\Resources\ImplementationPageResource;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImplementationPagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization,
        Implementation $implementation
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        return ImplementationPageResource::collection($implementation->pages);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationPage $implementationPage
     * @return ImplementationPageResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Implementation $implementation,
        ImplementationPage $implementationPage
    ): ImplementationPageResource {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        return new ImplementationPageResource($implementationPage);
    }

    /**
     * Display the specified resource.
     *
     * @param UpdateImplementationPageRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationPage $implementationPage
     * @return ImplementationPageResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateImplementationPageRequest $request,
        Organization $organization,
        Implementation $implementation,
        ImplementationPage $implementationPage
    ): ImplementationPageResource {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);

        $implementationPage->change($request->only(
            'content', 'content_alignment', 'external', 'external_url', 'media_uid', 'blocks', 'page_type'
        ));

        return new ImplementationPageResource($implementationPage);
    }
}