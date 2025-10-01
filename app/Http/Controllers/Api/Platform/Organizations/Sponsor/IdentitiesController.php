<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Exports\IdentityProfilesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\BankAccounts\StoreProfileBankAccountRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\BankAccounts\UpdateProfileBankAccountRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\IdentitiesPersonRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\IndexIdentitiesRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\StoreIdentityRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\StoreIdentityNoteRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\UpdateIdentityRequest;
use App\Http\Requests\BaseIndexFormRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\Arr\IdentityPersonArrResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\Identity;
use App\Models\Note;
use App\Models\Organization;
use App\Models\ProfileBankAccount;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\Sponsor\IdentitiesSearch;
use App\Services\PersonBsnApiService\PersonBsnApiManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class IdentitiesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        IndexIdentitiesRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('indexSponsorIdentities', [$organization]);

        $query = IdentityQuery::relatedToOrganization(Identity::query(), $organization->id);

        $search = new IdentitiesSearch([
            ...$request->only([
                'q', 'fund_id', 'birth_date_from', 'birth_date_to', 'postal_code', 'city', 'has_bsn',
                'municipality_name', 'last_activity_from', 'last_activity_to', 'last_login_from',
                'last_login_to', 'order_by', 'order_dir', 'household_id',
                'exclude_id', 'exclude_relation_id', 'exclude_household_id',
            ]),
            'organization_id' => $organization->id,
        ], $query);

        return SponsorIdentityResource::queryCollection($search->query(), $request, [
            'detailed' => true,
            'organization' => $organization,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function store(
        StoreIdentityRequest $request,
        Organization $organization,
    ): SponsorIdentityResource {
        $this->authorize('storeSponsorIdentities', [$organization]);

        $employee = $request->employee($organization);
        $identity = Identity::build(type: Identity::TYPE_PROFILE, employeeId: $employee->id, organizationId: $organization->id);

        $organization->findOrMakeProfile($identity)->updateRecords(array_only($request->validated(), [
            'given_name', 'family_name', 'telephone', 'mobile', 'birth_date', 'city',
            'street', 'house_number', 'house_number_addition', 'postal_code',
            'house_composition', 'gender', 'neighborhood_name', 'municipality_name',
            'living_arrangement', 'marital_status', 'client_number',
        ]), $request->employee($organization));

        return SponsorIdentityResource::create($identity, [
            'detailed' => true,
            'organization' => $organization,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(
        Organization $organization,
        Identity $identity,
    ): SponsorIdentityResource {
        $this->authorize('showSponsorIdentities', [$organization, $identity]);

        return SponsorIdentityResource::create($identity, [
            'detailed' => true,
            'organization' => $organization,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateIdentityRequest $request,
        Organization $organization,
        Identity $identity,
    ): SponsorIdentityResource {
        $this->authorize('updateSponsorIdentities', [$organization, $identity]);

        $organization->findOrMakeProfile($identity)->updateRecords(array_only($request->validated(), [
            'given_name', 'family_name', 'telephone', 'mobile', 'birth_date', 'city',
            'street', 'house_number', 'house_number_addition', 'postal_code',
            'house_composition', 'gender', 'neighborhood_name', 'municipality_name',
            'living_arrangement', 'marital_status', 'client_number',
        ]), $request->employee($organization));

        return SponsorIdentityResource::create($identity, [
            'detailed' => true,
            'organization' => $organization,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function storeBankAccount(
        StoreProfileBankAccountRequest $request,
        Organization $organization,
        Identity $identity,
    ): SponsorIdentityResource {
        $this->authorize('updateSponsorIdentities', [$organization, $identity]);

        $organization->findOrMakeProfile($identity)->profile_bank_accounts()->create([
            'name' => $request->validated('name'),
            'iban' => $request->validated('iban'),
        ]);

        return SponsorIdentityResource::create($identity, [
            'detailed' => true,
            'organization' => $organization,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateBankAccount(
        UpdateProfileBankAccountRequest $request,
        Organization $organization,
        Identity $identity,
        ProfileBankAccount $profileBankAccount,
    ): SponsorIdentityResource {
        $this->authorize('updateSponsorIdentitiesBankAccounts', [$organization, $identity, $profileBankAccount]);

        $profileBankAccount->update([
            'name' => $request->validated('name'),
            'iban' => $request->validated('iban'),
        ]);

        return SponsorIdentityResource::create($identity, [
            'detailed' => true,
            'organization' => $organization,
        ]);
    }

    /**
     * Delete the specified resource in storage.
     */
    public function deleteBankAccount(
        Organization $organization,
        Identity $identity,
        ProfileBankAccount $profileBankAccount,
    ): SponsorIdentityResource {
        $this->authorize('updateSponsorIdentitiesBankAccounts', [$organization, $identity, $profileBankAccount]);

        $profileBankAccount->delete();

        return SponsorIdentityResource::create($identity, [
            'detailed' => true,
            'organization' => $organization,
        ]);
    }

    /**
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('indexSponsorIdentities', [$organization]);

        return ExportFieldArrResource::collection(IdentityProfilesExport::getExportFields($organization));
    }

    /**
     * @param IndexIdentitiesRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return BinaryFileResponse
     */
    public function export(
        IndexIdentitiesRequest $request,
        Organization $organization,
    ): BinaryFileResponse {
        $this->authorize('indexSponsorIdentities', [$organization]);

        $fields = $request->input(
            'fields',
            array_pluck(IdentityProfilesExport::getExportFields($organization), 'key')
        );

        $fileData = new IdentityProfilesExport($request, $organization, $fields);
        $fileName = date('Y-m-d H:i:s') . '.' . $request->input('data_format', 'xls');

        return resolve('excel')->download($fileData, $fileName);
    }

    /**
     * @param IdentitiesPersonRequest $request
     * @param Organization $organization
     * @param Identity $identity
     * @throws Throwable
     * @return IdentityPersonArrResource
     */
    public function person(
        IdentitiesPersonRequest $request,
        Organization $organization,
        Identity $identity
    ) {
        $this->authorize('viewPersonBSNData', [$organization, $identity]);

        $bsn = $identity->bsn;
        $bsnService = PersonBsnApiManager::make($organization)->driver();
        $person = $bsnService->getPerson($bsn, ['parents', 'children', 'partners']);

        $scope = $request->input('scope');
        $scope_id = $request->input('scope_id');

        if ($person && $person->response()->success() && $scope && $scope_id) {
            if (!$relation = $person->getRelatedByIndex($scope, $scope_id)) {
                abort(404, 'Relation not found.');
            }

            $person = $relation->getBSN() ? $bsnService->getPerson($relation->getBSN()) : $relation;
        }

        if (!$person || $person->response() && $person->response()->error()) {
            if ($person && $person->response()->getCode() === 404) {
                abort(404, 'iConnect error, person not found.');
            }

            $errorMessage = $person ? 'Person bsn service, unknown error.' : 'Person bsn service, connection error.';

            Log::channel('person_bsn_api')->debug($errorMessage);
            abort(400, $errorMessage);
        }

        return new IdentityPersonArrResource($person);
    }

    /**
     * Display the specified resource.
     *
     * @param BaseIndexFormRequest $request
     * @param Organization $organization
     * @param Identity $identity
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function notes(
        BaseIndexFormRequest $request,
        Organization $organization,
        Identity $identity
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyIdentityNoteAsSponsor', [$organization, $identity]);

        return NoteResource::queryCollection($identity->notes(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreIdentityNoteRequest $request
     * @param Organization $organization
     * @param Identity $identity
     * @throws AuthorizationException
     * @return NoteResource
     * @noinspection PhpUnused
     */
    public function storeNote(
        StoreIdentityNoteRequest $request,
        Organization $organization,
        Identity $identity
    ): NoteResource {
        $this->authorize('storeIdentityNoteAsSponsor', [$organization, $identity]);

        return NoteResource::create($identity->addNote(
            $request->input('description'),
            $request->employee($organization),
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Identity $identity
     * @param Note $note
     * @throws AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function destroyNote(
        Organization $organization,
        Identity $identity,
        Note $note,
    ): JsonResponse {
        $this->authorize('destroyIdentityNoteAsSponsor', [$organization, $identity, $note]);

        $note->delete();

        return new JsonResponse();
    }
}
