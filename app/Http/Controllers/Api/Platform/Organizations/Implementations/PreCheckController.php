<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\PreChecks\SyncPreCheckRequest;
use App\Http\Resources\ImplementationPreChecksResource;
use App\Models\Implementation;
use App\Models\Organization;

class PreCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPreChecksResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization,
        Implementation $implementation,
    ): ImplementationPreChecksResource {
        $this->authorize('show', $organization);
        $this->authorize('updatePreChecks', [$implementation, $organization]);

        return ImplementationPreChecksResource::create($implementation);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SyncPreCheckRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPreChecksResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function syncPreChecks(
        SyncPreCheckRequest $request,
        Organization $organization,
        Implementation $implementation,
    ): ImplementationPreChecksResource {
        $this->authorize('show', $organization);
        $this->authorize('updatePreChecks', [$implementation, $organization]);

        $implementation->update($request->only([
            'pre_check_enabled', 'pre_check_title', 'pre_check_description'
        ]));

        if ($request->get('pre_checks')) {
            $implementation->syncPreChecks($request->input('pre_checks'));
        }

        if ($request->get('fund_exclusion')) {
            $fund = $organization->funds()->where([
                'id' => $request->input('fund_exclusion.fund_id'),
            ])->first();

            $fund?->updatePreCheckExclusion(
                $request->boolean('fund_exclusion.excluded'),
                $request->string('fund_exclusion.note') ?: null,
            );
        }

        return ImplementationPreChecksResource::create($implementation);
    }
}
