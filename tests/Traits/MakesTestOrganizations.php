<?php

namespace Tests\Traits;

use App\Models\BusinessType;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Role;

trait MakesTestOrganizations
{
    /**
     * @param Identity $identity
     * @param array $organizationData
     * @return Organization
     */
    public function makeTestProviderOrganization(Identity $identity, array $organizationData = []): Organization
    {
        $organization = $this->makeTestOrganization($identity, [
            'reservations_enabled' => true,
            'reservation_allow_extra_payments' => true,
            ...$organizationData,
        ]);

        return $organization->refresh();
    }

    /**
     * @param Identity $identity
     * @param array $organizationData
     * @return Organization
     */
    protected function makeTestOrganization(Identity $identity, array $organizationData = []): Organization
    {
        $organization = Organization::forceCreate([
            'name' => fake()->text(30),
            'email' => fake()->email,
            'email_public' => false,
            'phone' => fake()->phoneNumber,
            'phone_public' => false,
            'website' => fake()->url,
            'website_public' => false,
            'description' => fake()->text(400),
            'business_type_id' => BusinessType::pluck('id')->random(),
            'btw' => '',
            'iban' => fake()->iban('NL'),
            'identity_address' => $identity->address,
            ...$organizationData,
        ]);

        $organization->addEmployee($identity, Role::pluck('id')->toArray());

        return $organization;
    }
}
