<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImplementationPageConfigResource;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\ImplementationPageConfig;
use App\Models\Organization;
use Illuminate\Http\Request;
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
    public function show(
        Organization $organization,
        Implementation $implementation
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);

        return ImplementationPageConfigResource::collection(
            ImplementationPageConfig::query()->where('implementation_id', $implementation->id)->get()
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPageConfigResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        Request $request,
        Organization $organization,
        Implementation $implementation,
    ): ImplementationPageConfigResource {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);

        $data = [];

        if ($implementationConfig = ImplementationPage::find($data['page_id'] ?? null)) {
            $implementationConfig = $implementationConfig->update($data);
        } else {
            $implementationConfig = ImplementationPageConfig::query()->create($data);
        }

        return new ImplementationPageConfigResource($implementationConfig);
    }
}
