<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationSocialMedia\IndexImplementationSocialMediaRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationSocialMedia\StoreImplementationSocialMediaRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationSocialMedia\UpdateImplementationSocialMediaRequest;
use App\Http\Resources\ImplementationSocialMediaResource;
use App\Models\Implementation;
use App\Models\ImplementationSocialMedia;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImplementationSocialMediaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexImplementationSocialMediaRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexImplementationSocialMediaRequest $request,
        Organization $organization,
        Implementation $implementation
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('view', [$implementation, $organization]);
        $this->authorize('updateCMS', [$implementation, $organization]);

        return ImplementationSocialMediaResource::queryCollection(
            $implementation->social_medias(),
            $request,
        );
    }

    /**
     * Display the specified resource.
     *
     * @param StoreImplementationSocialMediaRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationSocialMediaResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreImplementationSocialMediaRequest $request,
        Organization $organization,
        Implementation $implementation
    ): ImplementationSocialMediaResource {
        $this->authorize('show', $organization);
        $this->authorize('view', [$implementation, $organization]);
        $this->authorize('updateCMS', [$implementation, $organization]);

        $socialMedia = $implementation->social_medias()->create($request->only([
            'type', 'url', 'title',
        ]));

        return new ImplementationSocialMediaResource($socialMedia);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationSocialMedia $socialMedia
     * @return ImplementationSocialMediaResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Implementation $implementation,
        ImplementationSocialMedia $socialMedia
    ): ImplementationSocialMediaResource {
        $this->authorize('show', $organization);
        $this->authorize('view', [$implementation, $organization]);
        $this->authorize('updateCMS', [$implementation, $organization]);

        return new ImplementationSocialMediaResource($socialMedia);
    }

    /**
     * Display the specified resource.
     *
     * @param UpdateImplementationSocialMediaRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationSocialMedia $socialMedia
     * @return ImplementationSocialMediaResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateImplementationSocialMediaRequest $request,
        Organization $organization,
        Implementation $implementation,
        ImplementationSocialMedia $socialMedia
    ): ImplementationSocialMediaResource {
        $this->authorize('show', $organization);
        $this->authorize('view', [$implementation, $organization]);
        $this->authorize('updateCMS', [$implementation, $organization]);

        $socialMedia->update($request->only([
            'type', 'url', 'title',
        ]));

        return new ImplementationSocialMediaResource($socialMedia);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationSocialMedia $socialMedia
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        Organization $organization,
        Implementation $implementation,
        ImplementationSocialMedia $socialMedia
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('view', [$implementation, $organization]);
        $this->authorize('updateCMS', [$implementation, $organization]);

        $socialMedia->delete();

        return new JsonResponse();
    }
}