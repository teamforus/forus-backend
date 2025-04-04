<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Offices\IndexOfficeRequest;
use App\Http\Requests\Api\Platform\Organizations\Offices\StoreOfficeRequest;
use App\Http\Requests\Api\Platform\Organizations\Offices\UpdateOfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Organization;
use App\Searches\OfficeSearch;
use App\Services\MediaService\Models\Media;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfficesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexOfficeRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexOfficeRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyPublic', [Office::class, $organization]);

        $search = new OfficeSearch(
            $request->only('q'),
            $organization->offices(),
            $organization->identityCan($request->identity(), 'manage_offices'),
        );

        return OfficeResource::queryCollection($search->query(), $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOfficeRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OfficeResource
     */
    public function store(StoreOfficeRequest $request, Organization $organization): OfficeResource
    {
        $this->authorize('show', $organization);
        $this->authorize('store', [Office::class, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $media = resolve('media')->findByUid($media_uid);
            $this->authorize('destroy', $media);
        }

        /** @var Office $office */
        $office = $organization->offices()->create($request->only([
            'name', 'address', 'phone', 'email', 'branch_id', 'branch_name', 'branch_number',
        ]));

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OfficeResource
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OfficeResource
     */
    public function update(
        UpdateOfficeRequest $request,
        Organization $organization,
        Office $office,
    ): OfficeResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$office, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $media = resolve('media')->findByUid($media_uid);
            $this->authorize('destroy', $media);
        }

        $office->update($request->only([
            'name', 'address', 'phone', 'email',
            'branch_id', 'branch_name', 'branch_number',
        ]));

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws Exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Organization $organization, Office $office): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$office, $organization]);

        $office->delete();

        return new JsonResponse([]);
    }
}
