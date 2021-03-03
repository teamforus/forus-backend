<?php

namespace App\Policies;

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
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(
        $identity_address,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity_address, 'manage_products');
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyPublic(): bool {
        return true;
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function store(
        $identity_address,
        Organization $organization
    ): bool {
        $hard_limit = config('forus.features.dashboard.organizations.products.hard_limit');
        $count_products = $organization->products()->whereDoesntHave('sponsor_organization')->count();
  
        return $organization->identityCan($identity_address, [
            'manage_products'
        ]) && $count_products < $hard_limit;
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(
        $identity_address,
        Product $product,
        Organization $organization
    ): bool {
        if ($product->organization_id !== $organization->id) {
            return false;
        }

        return $product->organization->identityCan($identity_address, 'manage_products');
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showFunds(
        $identity_address,
        Product $product,
        Organization $organization
    ): bool {
        return $this->show($identity_address, $product, $organization);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function showPublic(): bool {
        return true;
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function update(
        $identity_address,
        Product $product,
        Organization $organization
    ): bool {
        return $this->show($identity_address, $product, $organization) &&
            $product->sponsor_organization_id == null;
    }

    /**
     * To be able to make an reservation product should not
     * be expired or sold out and voucher not expired
     *
     * @param $identity_address
     * @param Voucher $voucher
     * @param Product $product
     * @return bool
     * @noinspection PhpUnused
     */
    public function reserve(
        $identity_address,
        Product $product,
        Voucher $voucher
    ): bool {
        if (empty($identity_address)) {
            return false;
        }

        // check validity
        return ProductQuery::approvedForFundsAndActiveFilter(
            Product::query(),
            $voucher->fund_id
        )->where('id', '=', $product->id)->exists();
    }

    /**
     * @param $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function destroy(
        $identity_address,
        Product $product,
        Organization $organization
    ): bool {
        return $this->show($identity_address, $product, $organization);
    }

    /**
     * @param string $identity_address
     * @param Organization $provider
     * @param Organization $sponsor
     * @return bool
     * @noinspection PhpUnused
     */
    public function listSponsorProduct(
        string $identity_address,
        Organization $provider,
        Organization $sponsor
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity_address, $sponsor, $provider);
    }

    /**
     * @param string $identity_address
     * @param Organization $provider
     * @param Organization $sponsor
     * @return bool
     * @noinspection PhpUnused
     */
    public function storeSponsorProduct(
        string $identity_address,
        Organization $provider,
        Organization $sponsor
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity_address, $sponsor, $provider);
    }

    /**
     * @param string $identity_address
     * @param Organization $provider
     * @param Organization $sponsor
     * @param Product $product
     * @return bool
     * @noinspection PhpUnused
     */
    public function showSponsorProduct(
        string $identity_address,
        Organization $provider,
        Organization $sponsor,
        Product $product
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity_address, $sponsor, $provider, $product);
    }

    /**
     * @param string $identity_address
     * @param Organization $provider
     * @param Organization $sponsor
     * @param Product $product
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateSponsorProduct(
        string $identity_address,
        Organization $provider,
        Organization $sponsor,
        Product $product
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity_address, $sponsor, $provider, $product);
    }

    /**
     * @param string $identity_address
     * @param Organization $provider
     * @param Organization $sponsor
     * @param Product $product
     * @return bool
     * @noinspection PhpUnused
     */
    public function destroySponsorProduct(
        string $identity_address,
        Organization $provider,
        Organization $sponsor,
        Product $product
    ): bool {
        return $this->isSponsorProductThreeAuthorized($identity_address, $sponsor, $provider, $product);
    }

    /**
     * @param string $identity_address
     * @param Organization $sponsor
     * @param Organization $provider
     * @param Product|null $product
     * @return bool
     */
    public function isSponsorProductThreeAuthorized(
        string $identity_address,
        Organization $sponsor,
        Organization $provider,
        ?Product $product = null
    ): bool {
        $identityIsProviderManager = $sponsor->identityCan($identity_address, 'manage_providers');
        $isSponsorForTheProvider = OrganizationQuery::whereIsProviderOrganization(
            Organization::query(), $sponsor
        )->whereKey($provider->id)->exists();
        $isSponsorManagingProducts = $sponsor->funds()->where([
            'manage_provider_products' => true,
        ])->exists();

        return $identityIsProviderManager &&
            $isSponsorForTheProvider &&
            $isSponsorManagingProducts &&
            ($product ? $product->organization_id === $provider->id : true) &&
            ($product ? $product->sponsor_organization_id === $sponsor->id : true);
    }
}
