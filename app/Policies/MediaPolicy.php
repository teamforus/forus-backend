<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

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
        /** @var Product $product */
        if (($product = $media->mediable) instanceof Product) {
            $organizationQuery = OrganizationQuery::whereIsEmployee(Organization::query(), $identity->address)
                ->select('id');

            $funds = Fund::whereHas('organization', function (Builder $builder) use ($organizationQuery) {
                $builder->whereIn('organizations.id', $organizationQuery);
            })->pluck('id')->all();

            return FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query()->where('organization_id', $product->organization_id),
                $funds
            )->exists();
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
                'manage_implementation_cms',
            ]);
        }

        return $identity->address == $media->identity_address;
    }
}