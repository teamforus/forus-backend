<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\BankAccounts\StoreProfileBankAccountRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\BankAccounts\UpdateProfileBankAccountRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\IndexIdentitiesRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\UpdateIdentityRequest;
use App\Http\Resources\Sponsor\SponsorIdentityResource;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\ProfileBankAccount;
use App\Scopes\Builders\IdentityQuery;
use App\Searches\Sponsor\IdentitiesSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
                'q', 'fund_id',
            ]),
            'organization_id' => $organization->id,
        ], $query);

        return SponsorIdentityResource::queryCollection($search->query(), $request, [
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

        $organization->findOrMakeProfile($identity)->updateRecords($request->only([
            'given_name', 'family_name', 'telephone', 'mobile', 'birth_date', 'city',
            'street', 'house_number', 'house_number_addition', 'postal_code',
            'house_composition', 'gender', 'age', 'neighborhood_name', 'municipality_name',
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
            'name' => $request->string('name'),
            'iban' => $request->string('iban'),
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
            'name' => $request->string('name'),
            'iban' => $request->string('iban'),
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
}
