<?php

namespace App\Http\Resources;

use App\Models\FundProviderInvitation;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundProviderInvitationResource
 * @property FundProviderInvitation $resource
 * @package App\Http\Resources
 */
class FundProviderInvitationResource extends Resource
{
    public static $load = [];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $invitation = $this->resource;

        return collect($invitation)->only([
            'id', 'state', 'allow_budget', 'allow_products'
        ])->merge([
            'expired'               => $invitation->expired,
            'expire_at'             => $invitation->expire_at->format('Y-m-d H:i:s'),
            'expire_at_locale'      => format_date_locale($invitation->expire_at),
            'created_at'            => $invitation->created_at->format('Y-m-d H:i:s'),
            'created_at_locale'     => format_datetime_locale(
                $invitation->created_at
            ),
            'provider_organization' => new OrganizationResource(
                $invitation->organization
            ),
            'sponsor_organization'  => new OrganizationResource(
                $invitation->fund->organization
            ),
            'fund'                  => new FundResource($invitation->fund),
            'from_fund'             => new FundResource($invitation->from_fund),
        ])->toArray();
    }
}
