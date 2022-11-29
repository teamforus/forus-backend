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
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_products');
    }

    /**
     * @param Identity|null $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyPublic(?Identity $identity): bool
    {
        return !$identity || $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        $hard_limit = config('forus.features.dashboard.organizations.products.hard_limit');
        $count_products = $organization->products()->whereDoesntHave('sponsor_organization')->count();
  
        return $organization->identityCan($identity, [
            'manage_products',
        ]) && $count_products < $hard_limit;
    }

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
     * @param Product $product
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showFunds(
        Identity $identity,
        Product $product,
        Organization $organization
    ): bool {
        return $this->show($identity, $product, $organization);
    }

    /**
     * @param Identity|null $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function showPublic(?Identity $identity): bool
    {
        return !$identity || $identity->exists;
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
     * @return bool
     * @noinspection PhpUnused
     */
    public function removeBookmark(Identity $identity): bool
    {
        return $this->bookmark($identity);
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
     * To be able to make a reservation, product should not
     * be expired or sold out and voucher not expired
     *
     * @param Identity $identity
     * @param Product $product
     * @param Voucher $voucher
     * @return bool
     * @noinspection PhpUnused
     */
    public function reserve(
        Identity $identity,
        Product $product,
        Voucher $voucher
    ): bool {
        if (!$identity->exists) {
            return false;
        }

        // check validity
        return ProductQuery::approvedForFundsAndActiveFilter(
            Product::query(),
            $voucher->fund_id
        )->where('id', '=', $product->id)->exists();
    }

    /**
     * @param Identity $identity
     * @param Product $product
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function destroy(
        Identity $identity,
        Product $product,
        Organization $organization
    ): bool {
        return $this->show($identity, $product, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $provider
     * @param Organization $sponsor
     * @return bool
     * @noinspection PhpUnused
     */
    public function listSponsorProduct(
        Identity $identity,
        Organization $provider,
        Organization $sponsor
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity, $sponsor, $provider);
    }

    /**
     * @param Identity $identity
     * @param Organization $provider
     * @param Organization $sponsor
     * @return bool
     * @noinspection PhpUnused
     */
    public function storeSponsorProduct(
        Identity $identity,
        Organization $provider,
        Organization $sponsor
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity, $sponsor, $provider);
    }

    /**
     * @param Identity $identity
     * @param Product $product
     * @param Organization $provider
     * @param Organization $sponsor
     * @return bool
     * @noinspection PhpUnused
     */
    public function showSponsorProduct(
        Identity $identity,
        Product $product,
        Organization $provider,
        Organization $sponsor
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity, $sponsor, $provider, $product);
    }

    /**
     * @param Identity $identity
     * @param Product $product
     * @param Organization $provider
     * @param Organization $sponsor
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateSponsorProduct(
        Identity $identity,
        Product $product,
        Organization $provider,
        Organization $sponsor
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity, $sponsor, $provider, $product);
    }

    /**
     * @param Identity $identity
     * @param Product $product
     * @param Organization $provider
     * @param Organization $sponsor
     * @return bool
     * @noinspection PhpUnused
     */
    public function destroySponsorProduct(
        Identity $identity,
        Product $product,
        Organization $provider,
        Organization $sponsor
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity, $sponsor, $provider, $product);
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
