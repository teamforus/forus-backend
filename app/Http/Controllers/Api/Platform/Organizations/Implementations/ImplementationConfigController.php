<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\UpdateImplementationConfigRequest;
use App\Http\Resources\ImplementationPageConfigResource;
use App\Models\Implementation;
use App\Models\ImplementationPageConfig;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImplementationConfigController extends Controller
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

        return ImplementationPageConfigResource::collection(
            $implementation->getImplementationConfig()
        );
    }

    /**
     * Update the specified resource.
     *
     * @param UpdateImplementationConfigRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPageConfigResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateImplementationConfigRequest $request,
        Organization $organization,
        Implementation $implementation,
    ): ImplementationPageConfigResource {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);

        foreach($request->input('config') as $config) {
            if (isset($config['id']) && $config['id']) {
                ImplementationPageConfig::find($config['id'])->update([
                    'is_active' => $config['is_active']
                ]);
            } else {
                $implementation->implementation_configs()->create($config);
            }
        }

        return new ImplementationPageConfigResource($implementation->implementation_configs);
    }
}