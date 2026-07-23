<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages\IndexImplementationPageCmsBlockConfigsRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages\StoreImplementationPageRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages\UpdateImplementationPageRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages\ValidateImplementationPageBlocksRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\ImplementationPages\ValidateImplementationPageCmsBlocksRequest;
use App\Http\Resources\Sponsor\ImplementationPageResource;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockSyncService;
use App\Services\CmsService\ImplementationBlocks\Resources\ImplementationCmsBlockConfigResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Organization $organization,
        Implementation $implementation
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);
        $this->authorize('viewAny', [ImplementationPage::class, $implementation, $organization]);

        return ImplementationPageResource::createCollection($implementation->pages);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreImplementationPageRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws AuthorizationException|Throwable
     * @return ImplementationPageResource
     */
    public function store(
        StoreImplementationPageRequest $request,
        Organization $organization,
        Implementation $implementation
    ): ImplementationPageResource {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);
        $this->authorize('create', [ImplementationPage::class, $implementation, $organization]);

        $cmsBlockSyncService = resolve(ImplementationCmsBlockSyncService::class);
        $pageType = $request->input('page_type');
        $isInternalType = ImplementationPage::isInternalType($pageType);

        $implementationPage = DB::transaction(function () use (
            $cmsBlockSyncService,
            $implementation,
            $isInternalType,
            $request,
        ) {
            /** @var ImplementationPage $implementationPage */
            $implementationPage = $implementation->pages()->create(array_merge($request->only([
                'title', 'description', 'description_alignment', 'description_position',
                'external', 'external_url', 'page_type', 'state', 'blocks_per_row',
            ]), $isInternalType ? [
                'external' => false,
                'external_url' => null,
            ] : []));

            $implementationPage->syncMarkdownMedia('cms_media');
            $cmsBlockSyncService->sync($implementationPage, $request->validated('cms_blocks'));
            $implementationPage->syncBlocks($request->input('blocks'));

            if ($implementationPage->supportsFaq()) {
                $implementationPage->syncFaqOptional($request->input('faq'));
            }

            return $implementationPage;
        });

        return ImplementationPageResource::create($implementationPage);
    }

    /**
     * @param ValidateImplementationPageBlocksRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
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
     * @param IndexImplementationPageCmsBlockConfigsRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function cmsBlockConfigs(
        IndexImplementationPageCmsBlockConfigsRequest $request,
        Organization $organization,
        Implementation $implementation
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);

        $blockConfigs = $request->input('page_type') ?
            ImplementationCmsBlockService::getBlockConfigsForPageType($request->input('page_type')) :
            array_values(ImplementationCmsBlockService::getBlockConfigs());

        return ImplementationCmsBlockConfigResource::collection($blockConfigs);
    }

    /**
     * @param ValidateImplementationPageCmsBlocksRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function storeCmsBlocksValidate(
        ValidateImplementationPageCmsBlocksRequest $request,
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ImplementationPageResource
     */
    public function show(
        Organization $organization,
        Implementation $implementation,
        ImplementationPage $implementationPage
    ): ImplementationPageResource {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);
        $this->authorize('view', [$implementationPage, $implementation, $organization]);

        return ImplementationPageResource::create($implementationPage);
    }

    /**
     * Display the specified resource.
     *
     * @param UpdateImplementationPageRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationPage $implementationPage
     * @throws AuthorizationException|Throwable
     * @return ImplementationPageResource
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

        $cmsBlockSyncService = resolve(ImplementationCmsBlockSyncService::class);
        $isInternalType = ImplementationPage::isInternalType($implementationPage->page_type);

        $data = array_merge($request->only([
            'title', 'state', 'description', 'description_position', 'description_alignment',
            'external', 'external_url', 'blocks_per_row',
        ]), $isInternalType ? [
            'external' => false,
            'external_url' => null,
        ] : []);

        DB::transaction(function () use ($cmsBlockSyncService, $data, $implementationPage, $request) {
            $implementationPage->update($data);
            $implementationPage->syncMarkdownMedia('cms_media');
            $cmsBlockSyncService->sync($implementationPage, $request->validated('cms_blocks'));
            $implementationPage->syncBlocks($request->input('blocks'));

            if ($implementationPage->supportsFaq()) {
                $implementationPage->syncFaqOptional($request->input('faq'));
            }
        });

        return ImplementationPageResource::create($implementationPage);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @param ImplementationPage $implementationPage
     * @throws Throwable
     * @return JsonResponse
     */
    public function destroy(
        Organization $organization,
        Implementation $implementation,
        ImplementationPage $implementationPage
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);
        $this->authorize('destroy', [$implementationPage, $implementation, $organization]);

        $cmsBlockSyncService = resolve(ImplementationCmsBlockSyncService::class);

        DB::transaction(function () use ($cmsBlockSyncService, $implementationPage) {
            $cmsBlockSyncService->sync($implementationPage, []);

            if ($implementationPage->page_type == 'home') {
                $implementationPage->implementation->pages
                    ?->where('page_type', ImplementationPage::TYPE_BLOCK_HOME_PRODUCTS)
                    ?->first()
                    ?->delete();

                $implementationPage->implementation->pages
                    ?->where('page_type', ImplementationPage::TYPE_BLOCK_HOME_PRODUCT_CATEGORIES)
                    ?->first()
                    ?->delete();
            }

            $implementationPage->delete();
        });

        return new JsonResponse();
    }
}
