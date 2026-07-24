<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue;
use App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @return mixed
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Media $media
     * @return bool
     */
    public function show(Identity $identity, Media $media): bool
    {
        return $identity->address == $media->identity_address;
    }

    /**
     * @param Identity $identity
     * @return bool
     */
    public function store(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Media $media
     * @return bool
     */
    public function clone(Identity $identity, Media $media): bool
    {
        if ($media->mediable instanceof Product) {
            // Organizations where identity manages providers and has at least one configured fund
            $organizations = OrganizationQuery::whereHasPermissions(
                Organization::whereHas('funds', function (Builder $builder) {
                    return FundQuery::whereIsConfiguredByForus($builder);
                }),
                $identity->address,
                Permission::MANAGE_PROVIDERS
            )->get();

            // At least one of the organizations must be able to clone products from the product provider
            foreach ($organizations as $organization) {
                if (Gate::allows('storeSponsorProduct', [
                    Product::class,
                    $media->mediable->organization,
                    $organization,
                ])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Identity $identity
     * @param Media $media
     * @return bool
     */
    public function destroy(Identity $identity, Media $media): bool
    {
        if ($media->mediable && in_array($media->type, ['implementation_banner', 'email_logo'])) {
            $implementation = $media->mediable instanceof Implementation ? $media->mediable : null;

            return $implementation?->organization->identityCan($identity, [
                Permission::MANAGE_IMPLEMENTATION_CMS,
            ]);
        }

        if ($media->mediable && $media->type === 'product_photo') {
            $product = $media->mediable instanceof Product ? $media->mediable : null;
            $managesProduct = Gate::allows('update', [$product, $product->organization]);

            $managesProviderProduct =
                $product?->sponsor_organization &&
                Gate::allows('updateSponsorProduct', [
                    $product, $product?->organization, $product?->sponsor_organization,
                ]);

            return $managesProduct || $managesProviderProduct;
        }

        if (
            $media->mediable instanceof ImplementationCmsBlockValue &&
            $media->type === 'implementation_block_media'
        ) {
            $organization = $media->mediable
                ->implementation_cms_block
                ?->implementation_page
                ?->implementation
                ?->organization;

            return $organization?->identityCan($identity, Permission::MANAGE_IMPLEMENTATION_CMS) ?: false;
        }

        if (
            $media->mediable instanceof ImplementationCmsBlockItemValue &&
            $media->type === 'implementation_block_media'
        ) {
            $organization = $media->mediable
                ->implementation_cms_block_item
                ?->implementation_cms_block
                ?->implementation_page
                ?->implementation
                ?->organization;

            return $organization?->identityCan($identity, Permission::MANAGE_IMPLEMENTATION_CMS) ?: false;
        }

        return $identity->address == $media->identity_address;
    }
}
