<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Profile\ShowProfileRequest;
use App\Http\Requests\Api\Platform\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\Sponsor\SponsorIdentityResource;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param ShowProfileRequest $request
     * @return ProfileResource
     */
    public function profile(ShowProfileRequest $request): ProfileResource
    {
        $identity = $request->identity();
        $organization = $request->implementation()?->organization;

        $profile = $organization?->allow_profiles ?
            $identity->profiles?->firstWhere('organization_id', $organization->id) :
            null;

        return ProfileResource::create($request->identity(), $organization?->allow_profiles ? [
            'profile' => true,
            'records' => SponsorIdentityResource::getProfileRecords($profile),
            'bank_accounts' => SponsorIdentityResource::getBankAccounts($identity, $organization, $profile),
        ] : [
            'profile' => false,
        ]);
    }

    /**
     * @param UpdateProfileRequest $request
     * @return ProfileResource
     */
    public function updateProfile(UpdateProfileRequest $request): ProfileResource
    {
        $identity = $request->identity();
        $organization = $request->implementation()?->organization;

        $profile = $organization?->findOrMakeProfile($identity);

        $profile->updateRecords($request->only([
            'telephone', 'mobile', 'city', 'street', 'house_number', 'house_number_addition', 'postal_code',
        ]));

        return ProfileResource::create($request->identity(), $organization?->allow_profiles ? [
            'profile' => true,
            'records' => SponsorIdentityResource::getProfileRecords($profile),
            'bank_accounts' => SponsorIdentityResource::getBankAccounts($identity, $organization, $profile),
        ] : [
            'profile' => false,
        ]);
    }
}
