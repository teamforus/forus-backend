<?php

namespace App\Http\Resources;

use App\Models\FundProviderInvitation;
use Illuminate\Http\Request;

/**
 * @property FundProviderInvitation $resource
 */
class FundProviderInvitationResource extends BaseJsonResource
{
    public const array LOAD = [];

    public const array LOAD_NESTED = [
        'fund' => FundResource::class,
        'fund.organization' => OrganizationResource::class,
        'from_fund' => FundResource::class,
        'organization' => OrganizationResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $invitation = $this->resource;

        return array_merge($invitation->only([
            'id', 'state', 'allow_budget', 'allow_products', 'expired',
        ]), [
            'fund' => new FundResource($invitation->fund),
            'from_fund' => new FundResource($invitation->from_fund),
            'provider_organization' => new OrganizationResource($invitation->organization),
            'sponsor_organization' => new OrganizationResource($invitation->fund->organization),
            'can_be_accepted' => $invitation->canBeAccepted(),
        ], $this->timestamps($invitation, 'created_at', 'expire_at'));
    }
}
