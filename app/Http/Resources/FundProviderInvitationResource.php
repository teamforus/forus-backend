<?php

namespace App\Http\Resources;

use App\Models\FundProviderInvitation;

/**
 * @property FundProviderInvitation $resource
 */
class FundProviderInvitationResource extends BaseJsonResource
{
    public const LOAD = [
        'fund',
        'from_fund',
        'organization',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $invitation = $this->resource;

        return array_merge($invitation->only([
            'id', 'state', 'allow_budget', 'allow_products', 'expired',
        ]), [
            'fund'                  => new FundResource($invitation->fund),
            'from_fund'             => new FundResource($invitation->from_fund),
            'provider_organization' => new OrganizationResource($invitation->organization),
            'sponsor_organization'  => new OrganizationResource($invitation->fund->organization),
            'can_be_accepted'       => $invitation->canBeAccepted(),
        ], $this->timestamps($invitation, 'created_at', 'expire_at'));
    }
}
