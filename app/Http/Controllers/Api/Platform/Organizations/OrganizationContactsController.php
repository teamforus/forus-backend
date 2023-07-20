<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\OrganizationContacts\IndexOrganizationContactsRequest;
use App\Http\Requests\Api\Platform\OrganizationContacts\StoreOrganizationContactsRequest;
use App\Http\Resources\OrganizationContactResource;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class OrganizationContactsController extends Controller
{
    /**
     * Display a listing of all identity organizations.
     *
     * @param IndexOrganizationContactsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexOrganizationContactsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [OrganizationContact::class, $organization]);

        $organizations = $organization->contacts()->orderBy('contact_key')->get();

        return OrganizationContactResource::collection($organizations);
    }

    /**
     * Store a newly created identity organization in storage.
     *
     * @param StoreOrganizationContactsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function store(
        StoreOrganizationContactsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('store', [OrganizationContact::class, $organization]);

        $organization->contacts()->delete();
        foreach ($request->get('contacts', []) as $contact) {
            $organization->contacts()->create(Arr::only($contact, [
                'type', 'contact_key', 'value',
            ]));
        }

        $organizations = $organization->contacts()->orderBy('contact_key')->get();

        return OrganizationContactResource::collection($organizations);
    }

    /**
     * @param Organization $organization
     * @return JsonResponse
     */
    public function available(Organization $organization)
    {
        $this->authorize('viewAny', [OrganizationContact::class, $organization]);

        return new JsonResponse([
            'data' => OrganizationContact::$availableContacts
        ]);
    }
}
