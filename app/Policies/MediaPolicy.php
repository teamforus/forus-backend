<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class MediaPolicy
{
    use HandlesAuthorization;

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
                Organization::whereHas('funds', function(Builder $builder) {
                    return FundQuery::whereIsConfiguredByForus($builder);
                }),
                $identity->address,
                'manage_providers'
            )->get();

            // At least one of the organizations must be able to clone products from the product provider
            foreach ($organizations as $organization) {
                if (Gate::allows('storeSponsorProduct', [
                    Product::class,
                    $media->mediable->organization,
                    $organization
                ])) {
                    return true;
                }
            }
        }

        return false;
    }
}