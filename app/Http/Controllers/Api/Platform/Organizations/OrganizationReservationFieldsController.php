<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\OrganizationReservationFields\IndexOrganizationReservationFieldsRequest;
use App\Http\Resources\OrganizationReservationFieldResource;
use App\Models\OrganizationReservationField;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationReservationFieldsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexOrganizationReservationFieldsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexOrganizationReservationFieldsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [OrganizationReservationField::class, $organization]);

        return OrganizationReservationFieldResource::queryCollection(
            $organization->reservation_fields(),
            $request,
        );
    }
}
