<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Offices\StoreOfficeRequest;
use App\Http\Requests\Api\Platform\Organizations\Offices\UpdateOfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class OfficesController extends Controller
{
    protected $geocodeService;
    protected $mediaService;

    public function __construct()
    {
        $this->geocodeService = app()->make('geocode_api');
        $this->mediaService = app()->make('media');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('index', [Office::class, $organization]);

        return OfficeResource::collection($organization->offices);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOfficeRequest $request
     * @param Organization $organization
     * @return OfficeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreOfficeRequest $request,
        Organization $organization
    ) {
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

        $schedules = collect($request->input('schedule', []))->filter(
            function($val, $key) {
                return in_array($key, range(0, 6));
            }
        )->map(function($schedule) {
            if ($schedule['start_time'] == 'null') {
                $schedule['start_time'] = null;
            }

            if ($schedule['end_time'] == 'null') {
                $schedule['end_time'] = null;
            }

            return $schedule;
        });

        foreach ($schedules as $week_day => $schedule) {
            $office->schedules()->create(array_merge(
                $schedule,
                compact('week_day')
            ));
        }

        $office->load('schedules');

        if ($coordinates = $this->geocodeService->getCoordinates(
            $office->address
        )) {
            $office->update($coordinates);
        }

        if ($media && $media->type == 'office_photo') {
            $office->attachMedia($media);
        }

        return new OfficeResource($office);
    }

    /**
     * @param Organization $organization
     * @param Office $office
     * @return OfficeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Office $office
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', [$office, $organization]);

        return new OfficeResource($office);
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
    ) {
        $this->authorize('show', $organization);
        $this->authorize('update', [$office, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $media = $this->mediaService->findByUid($media_uid);
            $this->authorize('destroy', $media);
        }

        /** @var Office $office */
        $office->update(
            $request->only(['name', 'address', 'phone', 'email'])
        );

        $schedules = collect($request->input('schedule', []))->filter(
            function($val, $key) {
                return in_array($key, range(0, 6));
            }
        )->map(function($schedule) {
            if ($schedule['start_time'] == 'null') {
                $schedule['start_time'] = null;
            }

            if ($schedule['end_time'] == 'null') {
                $schedule['end_time'] = null;
            }

            return $schedule;
        });

        $office->schedules()->whereNotIn(
            'week_day', $schedules->keys()->toArray()
        )->delete();

        foreach ($schedules as $week_day => $schedule) {
            $office->schedules()->firstOrCreate(
                compact('week_day')
            )->update($schedule);
        }

        if ($coordinates = $this->geocodeService->getCoordinates(
            $office->address
        )) {
            $office->update($coordinates);
        }

        if ($media && $media->type == 'office_photo') {
            $office->attachMedia($media);
        }

        return new OfficeResource($office);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Office $office
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        Office $office
    ) {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$office, $organization]);

        $office->delete();

        return [];
    }
}
