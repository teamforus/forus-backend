<?php

namespace App\Http\Controllers\Api\Platform;

use App\Events\Organizations\OrganizationCreated;
use App\Events\Organizations\OrganizationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\IndexOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\TransferOrganizationOwnershipRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateBankStatementFieldsRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationAcceptReservationsRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationReservationSettingsRequest;
use App\Http\Requests\Api\Platform\Organizations\UpdateOrganizationRolesRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\OrganizationFeaturesResource;
use App\Http\Resources\OrganizationResource;
use App\Models\Employee;
use App\Models\Organization;
use App\Searches\OrganizationSearch;
use App\Services\MediaService\Models\Media;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OrganizationsController extends Controller
{
    /**
     * Display a listing of all identity organizations.
     *
     * @param IndexOrganizationRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexOrganizationRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Organization::class);

        $search = new OrganizationSearch([
            ...$request->only([
                'type', 'is_sponsor', 'is_provider', 'is_validator', 'q', 'has_reservations', 'order_by', 'order_dir',
            ]),
            'auth_address' => $request->auth_address(),
            'implementation_id' => $request->implementation()?->id,
        ], Organization::query());

        $organizations = $search->query()
            ->with(OrganizationResource::loadDeps($request))
            ->orderBy('name')
            ->paginate($request->input('per_page', 10));

        return OrganizationResource::collection($organizations);
    }

    /**
     * Store a newly created identity organization in storage.
     *
     * @param StoreOrganizationRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OrganizationResource
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OrganizationResource
     */
    public function show(BaseFormRequest $request, Organization $organization): OrganizationResource
    {
        $this->authorize('show', $organization);

        return new OrganizationResource($organization->load(OrganizationResource::loadDeps($request)));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateOrganizationRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OrganizationResource
     */
    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
    ): OrganizationResource {
        $this->authorize('update', $organization);

        if ($media = Media::findByUid($request->post('media_uid'))) {
            $this->authorize('destroy', $media);
        }

        $organization->update($request->only([
            'name', 'email', 'phone', 'kvk', 'btw', 'website',
            'email_public', 'phone_public', 'website_public',
            'business_type_id', 'description', 'auth_2fa_policy', 'auth_2fa_remember_ip',
            'translations_enabled', 'translations_daily_limit', 'translations_weekly_limit',
            'translations_monthly_limit',
        ]));

        if ($organization->allow_2fa_restrictions) {
            $organization->update($request->only([
                'auth_2fa_policy', 'auth_2fa_remember_ip',
                'auth_2fa_funds_policy', 'auth_2fa_funds_remember_ip',
                'auth_2fa_funds_restrict_emails', 'auth_2fa_funds_restrict_auth_sessions',
                'auth_2fa_funds_restrict_reimbursements', 'auth_2fa_restrict_bi_connections',
            ]));
        }

        if ($request->has('contacts') && is_array($request->get('contacts'))) {
            $organization->syncContacts($request->get('contacts'));
        }

        if ($request->has('iban') && Gate::allows('updateIban', $organization)) {
            $organization->update([
                'iban' => strtoupper($request->get('iban')),
            ]);
        }

        if ($media instanceof Media && $media->type === 'organization_logo') {
            $organization->attachMedia($media);
        }

        OrganizationUpdated::dispatch($organization);

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationRolesRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OrganizationResource
     * @noinspection PhpUnused
     */
    public function updateRoles(
        UpdateOrganizationRolesRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'is_sponsor', 'is_provider', 'is_validator',
        ])));

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateBankStatementFieldsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OrganizationResource
     * @noinspection PhpUnused
     */
    public function updateBankStatementFields(
        UpdateBankStatementFieldsRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'bank_transaction_id', 'bank_transaction_date', 'bank_transaction_time', 'bank_reservation_number',
            'bank_branch_number', 'bank_branch_id', 'bank_branch_name', 'bank_fund_name', 'bank_note',
            'bank_separator', 'bank_reservation_first_name', 'bank_reservation_last_name',
        ])));

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationAcceptReservationsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OrganizationResource
     * @noinspection PhpUnused
     */
    public function updateAcceptReservations(
        UpdateOrganizationAcceptReservationsRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('updateAutoAllowReservations', $organization);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'reservations_auto_accept',
        ])));

        return new OrganizationResource($organization);
    }

    /**
     * @param UpdateOrganizationReservationSettingsRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OrganizationResource
     * @noinspection PhpUnused
     */
    public function updateReservationFields(
        UpdateOrganizationReservationSettingsRequest $request,
        Organization $organization
    ): OrganizationResource {
        $this->authorize('update', $organization);

        $allowExtraPayments = Gate::allows('allowExtraPayments', [MollieConnection::class, $organization]);

        OrganizationUpdated::dispatch($organization->updateModel($request->only([
            'reservation_phone', 'reservation_address', 'reservation_birth_date',
            'reservation_user_note',
            ...$allowExtraPayments ? ['reservation_allow_extra_payments'] : [],
        ])));

        $organization->syncReservationFields($request->get('fields', []));

        return new OrganizationResource($organization);
    }

    /**
     * @param TransferOrganizationOwnershipRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
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
            'identity_address' => $employee->identity_address,
        ]);

        return new JsonResponse();
    }

    /**
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return OrganizationFeaturesResource
     */
    public function getFeatures(Organization $organization): OrganizationFeaturesResource
    {
        $this->authorize('showFeatures', $organization);

        return OrganizationFeaturesResource::create($organization);
    }
}
