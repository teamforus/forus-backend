<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\IndexImplementationRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\UpdateImplementationCmsRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\UpdateImplementationDigiDRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\UpdateImplementationEmailBrandingRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\UpdateImplementationEmailRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\UpdatePreCheckBannerRequest;
use App\Http\Resources\ImplementationPrivateResource;
use App\Models\Implementation;
use App\Models\Organization;
use App\Scopes\Builders\ImplementationQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImplementationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexImplementationRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexImplementationRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [Implementation::class, $organization]);

        $query = Implementation::whereOrganizationId($organization->id);

        if ($q = $request->input('q')) {
            $query = ImplementationQuery::whereQueryFilter($query, $q);
        }

        return ImplementationPrivateResource::queryCollection($query, $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPrivateResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Implementation $implementation
    ): ImplementationPrivateResource {
        $this->authorize('show', $organization);
        $this->authorize('view', [$implementation, $organization]);

        return new ImplementationPrivateResource($implementation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateImplementationCmsRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPrivateResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     * @noinspection PhpUnused
     */
    public function updateCMS(
        UpdateImplementationCmsRequest $request,
        Organization $organization,
        Implementation $implementation
    ): ImplementationPrivateResource {
        $this->authorize('show', $organization);
        $this->authorize('updateCMS', [$implementation, $organization]);

        $implementation->updateModel($request->only([
            'title', 'description', 'description_alignment', 'informal_communication',
            'overlay_enabled', 'overlay_type', 'overlay_opacity', 'header_text_color',
            'show_home_map', 'show_home_products', 'show_providers_map',
            'show_provider_map', 'show_office_map', 'show_voucher_map', 'show_product_map',
        ]));

        $implementation->addWebshopAnnouncement(
            $request->input('announcement', []),
            $request->boolean('announcement.replace')
        );

        $implementation->attachMediaByUid($request->input('banner_media_uid'));
        $implementation->syncDescriptionMarkdownMedia('cms_media');

        return new ImplementationPrivateResource($implementation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateImplementationDigiDRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPrivateResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updateDigiD(
        UpdateImplementationDigiDRequest $request,
        Organization $organization,
        Implementation $implementation
    ): ImplementationPrivateResource {
        $this->authorize('show', $organization);
        $this->authorize('updateDigiD', [$implementation, $organization]);

        $implementation->update($request->only([
            'digid_app_id', 'digid_shared_secret', 'digid_a_select_server',
        ]));

        return new ImplementationPrivateResource($implementation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateImplementationEmailRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPrivateResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updateEmail(
        UpdateImplementationEmailRequest $request,
        Organization $organization,
        Implementation $implementation
    ): ImplementationPrivateResource {
        $this->authorize('show', $organization);
        $this->authorize('updateEmail', [$implementation, $organization]);

        return new ImplementationPrivateResource($implementation->updateModel($request->only([
            'email_from_address', 'email_from_name',
        ])));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateImplementationEmailBrandingRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPrivateResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updateEmailBranding(
        UpdateImplementationEmailBrandingRequest $request,
        Organization $organization,
        Implementation $implementation
    ): ImplementationPrivateResource {
        $this->authorize('show', $organization);
        $this->authorize('updateEmailBranding', [$implementation, $organization]);

        return new ImplementationPrivateResource($implementation->updateModel($request->only([
            'email_color', 'email_signature',
        ]))->attachMediaByUid($request->input('email_logo_uid')));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePreCheckBannerRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @return ImplementationPrivateResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updatePreCheckBanner(
        UpdatePreCheckBannerRequest $request,
        Organization $organization,
        Implementation $implementation
    ): ImplementationPrivateResource {
        $this->authorize('show', $organization);
        $this->authorize('updatePreCheckBanner', [$implementation, $organization]);

        return new ImplementationPrivateResource($implementation->updateModel($request->only([
            'pre_check_homepage_title', 'pre_check_homepage_description', 'pre_check_homepage_label',
        ]))->attachMediaByUid($request->input('pre_check_media_uid')));
    }
}
