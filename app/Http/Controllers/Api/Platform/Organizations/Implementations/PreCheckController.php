<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\PreChecks\IndexPreCheckRequest;
use App\Http\Requests\Api\Platform\PreChecks\StorePreCheckRequest;
use App\Http\Requests\Api\Platform\PreChecks\UpdatePreCheckRequest;
use App\Http\Resources\PreCheckResource;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\PreCheck;
use App\Models\RecordType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PreCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexPreCheckRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexPreCheckRequest $request,
        Organization $organization,
        Implementation $implementation
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        return PreCheckResource::queryCollection($implementation->getPreChecks(), $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePreCheckRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function syncPreChecks(
        StorePreCheckRequest $request,
        Organization $organization,
        Implementation $implementation,
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);

        $implementation->update($request->only(
            'pre_check_enabled', 'pre_check_title', 'pre_check_description'
        ));

        $implementation->pre_checks()->delete();

        $preChecks = $request->input('preChecks');
        foreach ($preChecks as $index => $preCheckData) {
            /** @var PreCheck $preCheck */
            $preCheck = $implementation->pre_checks()->create([
                'title' => $preCheckData['title'],
                'description' => $preCheckData['description'] ?: '',
                'default' => $preCheckData['default'] ?? false,
                'order' => $index,
            ]);

            foreach ($preCheckData['pre_check_records'] as $recordIndex => $pre_check_record) {
                $preCheck->pre_check_records()->create([
                    'title' => $pre_check_record['title'],
                    'short_title' => $pre_check_record['short_title'],
                    'description' => $pre_check_record['description'] ?: '',
                    'order' => $recordIndex,
                    'record_type_id' => RecordType::findByKey($pre_check_record['record_type']['key'])->id,
                ]);
            }
        }

        return PreCheckResource::queryCollection($implementation->getPreChecks(), $request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePreCheckRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return PreCheckResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateBannerFields(
        UpdatePreCheckRequest $request,
        Organization $organization,
        Implementation $implementation
    ): PreCheckResource  {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        /** @var PreCheck $preCheck */
        $preCheck = $implementation->pre_checks()->updateOrCreate(
            $request->only(['name', 'title', 'description'])
        );

        return PreCheckResource::create($preCheck);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @param PreCheck $preCheck
     * @return PreCheckResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Implementation $implementation,
        PreCheck $preCheck
    ): PreCheckResource {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);
        $this->authorize('view', [$implementation, $organization]);

        return PreCheckResource::create($preCheck);
    }
}
