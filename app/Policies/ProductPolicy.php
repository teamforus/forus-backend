<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Voucher;
use App\Scopes\Builders\OrganizationQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Product $product
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(
        Identity $identity,
        Product $product,
        Organization $organization
    ): bool {
        if ($product->organization_id !== $organization->id) {
            return false;
        }

        return $product->organization->identityCan($identity, 'manage_products');
    }

    /**
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function bookmark(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Product $product
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function update(
        Identity $identity,
        Product $product,
        Organization $organization
    ): bool {
        return
            $this->show($identity, $product, $organization) &&
            $product->sponsor_organization_id == null;
    }

    /**
     * @param Identity $identity
     * @param Organization $sponsor
     * @param Organization $provider
     * @param Product|null $product
     * @return bool
     */
    public function isSponsorProductThreeAuthorized(
        Identity $identity,
        Organization $sponsor,
        Organization $provider,
        ?Product $product = null
    ): bool {
        $sponsorManagesProviderProducts = $sponsor->manage_provider_products;
        $identityIsManagingSponsorProviders = $sponsor->identityCan($identity, 'manage_providers');
        $sponsorIsActiveProviderSponsor = OrganizationQuery::whereIsProviderOrganization(
            Organization::query(), $sponsor
        )->whereKey($provider->id)->exists();

        return
            $sponsorManagesProviderProducts &&
            $identityIsManagingSponsorProviders &&
            $sponsorIsActiveProviderSponsor &&
            (!$product || $product->organization_id === $provider->id) &&
            (!$product || $product->sponsor_organization_id === $sponsor->id);
    }
}
