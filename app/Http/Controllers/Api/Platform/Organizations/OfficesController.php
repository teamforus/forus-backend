<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Offices\IndexOfficeRequest;
use App\Http\Requests\Api\Platform\Organizations\Offices\StoreOfficeRequest;
use App\Http\Requests\Api\Platform\Organizations\Offices\UpdateOfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Services\MediaService\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class OfficesController
 * @package App\Http\Controllers\Api\Platform\Organizations
 */
class OfficesController extends Controller
{
    protected $geocodeService;
    protected $mediaService;

    public function __construct()
    {
        $this->geocodeService = resolve('geocode_api');
        $this->mediaService = resolve('media');
    }

    /**
     * Display a listing of the resource.
     *
     * @param IndexOfficeRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexOfficeRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyPublic', [Office::class, $organization]);

        return OfficeResource::queryCollection($organization->offices(), $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOfficeRequest $request
     * @param Organization $organization
     * @return OfficeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreOfficeRequest $request, Organization $organization): OfficeResource
    {
        $this->authorize('show', $organization);
        $this->authorize('store', [Office::class, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $media = $this->mediaService->findByUid($media_uid);
            $this->authorize('destroy', $media);
        }

        /** @var Office $office */
        $office = $organization->offices()->create(
            $request->only(['name', 'address', 'phone', 'email'])
        );

        $office->updateSchedule($request->input('schedule', []));
        $office->updateGeoData();

        if ($media instanceof Media && $media->type === 'office_photo') {
            $office->attachMedia($media);
        }

        return OfficeResource::create($office);
    }

    /**
     * @param Organization $organization
     * @param Office $office
     * @return OfficeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Organization $organization, Office $office): OfficeResource
    {
        $this->authorize('show', $organization);
        $this->authorize('show', [$office, $organization]);

        return OfficeResource::create($office);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateOfficeRequest $request
     * @param Organization $organization
     * @param Office $office
     * @return OfficeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateOfficeRequest $request,
        Organization $organization,
        Office $office
    ): OfficeResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$office, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $media = $this->mediaService->findByUid($media_uid);
            $this->authorize('destroy', $media);
        }

        $office->update($request->only(['name', 'address', 'phone', 'email']));
        $office->updateSchedule($request->input('schedule', []));
        $office->updateGeoData();

        if ($media instanceof Media && $media->type === 'office_photo') {
            $office->attachMedia($media);
        }

        return OfficeResource::create($office);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Office $office
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(Organization $organization, Office $office): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$office, $organization]);

        $office->delete();

        return new JsonResponse([]);
    }
}
