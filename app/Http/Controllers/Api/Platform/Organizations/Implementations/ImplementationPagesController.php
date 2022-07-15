<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages\ValidateImplementationPageBlocksRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages\StoreImplementationPageRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages\UpdateImplementationPageRequest;
use App\Http\Resources\Sponsor\ImplementationPageResource;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @noinspection PhpUnused
 */
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
        $this->authorize('updateCMS', [$implementation, $organization]);
        $this->authorize('viewAny', [ImplementationPage::class, $implementation, $organization]);

        return ImplementationPageResource::collection($implementation->pages);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreImplementationPageRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPageResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreImplementationPageRequest $request,
        Organization $organization,
        Implementation $implementation
    ): ImplementationPageResource {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);
        $this->authorize('create', [ImplementationPage::class, $implementation, $organization]);

        $pageType = $request->input('page_type');
        $isInternalType = ImplementationPage::isInternalType($pageType);

        /** @var ImplementationPage $page */
        $page = $implementation->pages()->create(array_merge($request->only([
            'content', 'content_alignment', 'external', 'external_url', 'page_type', 'state',
        ]), $isInternalType ? [
            'external' => false,
            'external_url' => null,
        ] : []));

        $page->appendMedia($data['media_uid'] ?? [], 'implementation_block_media');
        $page->syncBlocks($request->input('blocks'));

        return new ImplementationPageResource($page);
    }

    /**
     * @param ValidateImplementationPageBlocksRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function storeBlocksValidate(
        ValidateImplementationPageBlocksRequest $request,
        Organization $organization,
        Implementation $implementation
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);

        return new JsonResponse([], $request->isAuthenticated() ? 200 : 403);
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
        $this->authorize('updateCMS', [$implementation, $organization]);
        $this->authorize('view', [$implementationPage, $implementation, $organization]);

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
        $this->authorize('update', [$implementationPage, $implementation, $organization]);

        $isInternalType = ImplementationPage::isInternalType($implementationPage->page_type);

        $data = array_merge($request->only([
            'state', 'content', 'content_alignment', 'external', 'external_url',
        ]), $isInternalType ? [
            'external' => false,
            'external_url' => null,
        ] : []);

        $implementationPage->update($data);
        $implementationPage->appendMedia($request->input('media_uid'), 'cms_media');
        $implementationPage->syncBlocks($request->input('blocks'));

        return new ImplementationPageResource($implementationPage);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationPage $implementationPage
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        Organization $organization,
        Implementation $implementation,
        ImplementationPage $implementationPage
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);
        $this->authorize('destroy', [$implementationPage, $implementation, $organization]);

        $implementationPage->delete();

        return new JsonResponse();
    }
}