<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Organizations\OrganizationCreated;
use App\Events\Organizations\OrganizationUpdated;
use App\Http\Requests\Api\Platform\Organizations\TransferOrganizationOwnershipRequest;
use App\Http\Requests\Api\Platform\Organizations\IndexOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationAcceptReservationsRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationBIConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationBusinessTypeRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationReservationSettingsRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationRolesRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Organization;
use App\Services\MediaService\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OrganizationsController extends Controller
{
    /**
     * Display a listing of all identity organizations.
     *
     * @param IndexOrganizationRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexOrganizationRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::searchQuery($request)
            ->with(OrganizationResource::load($request))
            ->orderBy('name')
            ->paginate($request->input('per_page', 10));

        return OrganizationResource::collection($organizations);
    }

    /**
     * Store a newly created identity organization in storage.
     *
     * @param StoreOrganizationRequest $request
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreOrganizationRequest $request): OrganizationResource
    {
        $this->authorize('store', Organization::class);

        if ($media = Media::findByUid($request->post('media_uid'))) {
            $this->authorize('destroy', $media);
        }

        /** @var Organization $organization */
        $organization = Organization::create(
            collect($request->only([
                'name', 'email', 'phone', 'kvk', 'website', 'description',
                'email_public', 'phone_public', 'website_public', 'business_type_id',
            ]))->merge([
                'btw' => (string) $request->get('btw', ''),
                'iban' => strtoupper($request->get('iban')),
                'identity_address' => $request->auth_address(),
            ])->toArray()
        );

        if ($media instanceof Media && $media->type === 'organization_logo') {
            $organization->attachMedia($media);
        }

        OrganizationCreated::dispatch($organization);

        return new OrganizationResource($organization);
    }

    /**
     * Display the specified resource.
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(BaseFormRequest $request, Organization $organization): OrganizationResource
    {
        $this->authorize('show', $organization);

        return new OrganizationResource($organization->load(OrganizationResource::load($request)));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateOrganizationRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        if ($media = Media::findByUid($request->post('media_uid'))) {
            $this->authorize('destroy', $media);
        }

        $organization->update($request->only([
            'name', 'email', 'phone', 'kvk', 'btw', 'website',
            'email_public', 'phone_public', 'website_public',
            'business_type_id', 'description', 'auth_2fa_policy', 'auth_2fa_remember_ip',
        ]));

        if ($organization->allow_2fa_restrictions) {
            $organization->update($request->only([
                'auth_2fa_policy', 'auth_2fa_remember_ip',
                'auth_2fa_funds_policy', 'auth_2fa_funds_remember_ip',
                'auth_2fa_funds_restrict_emails', 'auth_2fa_funds_restrict_auth_sessions',
                'auth_2fa_funds_restrict_reimbursements'
            ]));
        }

        if ($request->has('contacts') && is_array($request->get('contacts'))) {
            $organization->syncContacts($request->get('contacts'));
        }

        if ($request->has('iban') && Gate::allows('updateIban', $organization)) {
            $organization->update([
                'iban' => strtoupper($request->get('iban'))
            ]);
        }

        if ($media instanceof Media && $media->type === 'organization_logo') {
            $organization->attachMedia($media);
        }

        OrganizationUpdated::dispatch($organization);

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationBusinessTypeRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updateBusinessType(
        UpdateOrganizationBusinessTypeRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'business_type_id',
        ])));

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationRolesRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updateRoles(
        UpdateOrganizationRolesRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'is_sponsor', 'is_provider', 'is_validator',
            'validator_auto_accept_funds'
        ])));

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationAcceptReservationsRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updateAcceptReservations(
        UpdateOrganizationAcceptReservationsRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('updateAutoAllowReservations', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'reservations_auto_accept'
        ])));

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationReservationSettingsRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updateReservationFieldSettings(
        UpdateOrganizationReservationSettingsRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'reservation_phone', 'reservation_address', 'reservation_birth_date',
        ])));

        if ($organization->allow_reservation_custom_fields) {
            $organization->syncReservationFields($request->get('fields', []));
        }

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationBIConnectionRequest $request
     * @param Organization $organization
     * @return OrganizationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function updateBIConnection(
        UpdateOrganizationBIConnectionRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('updateBIConnection', $organization);

        $authType = $request->input('bi_connection_auth_type');
        $resetToken = $request->boolean('bi_connection_token_reset');

        if ($authType != $organization->bi_connection_auth_type || $resetToken) {
            $organization->updateBIConnection($authType, $resetToken);
        }

        return new OrganizationResource($organization);
    }

    /**
     * @param TransferOrganizationOwnershipRequest $request
     * @param Organization $organization
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function transferOwnership(
        TransferOrganizationOwnershipRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize('show', [$organization]);
        $this->authorize('transferOwnership', [$organization]);

        /** @var Employee $employee */
        $employee_id = $request->input('employee_id');
        $employee = $organization->employeesOfRoleQuery('admin')->find($employee_id);

        $organization->update([
            'identity_address' => $employee->identity_address
        ]);

        return new JsonResponse();
    }
}
