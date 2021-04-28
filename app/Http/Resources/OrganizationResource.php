<?php

namespace App\Http\Resources;

use Gate;
use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class OrganizationResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class OrganizationResource extends Resource
{
    public const DEPENDENCIES = [
        'logo',
        'funds',
        'funds_count',
        'business_type',
        'permissions',
    ];

    /**
     * @param null $request
     * @return array
     */
    public static function load($request = null): array {
        $load = [];

        self::isRequested('logo', $request) && array_push($load, 'logo');
        self::isRequested('funds', $request) && array_push($load, 'funds');
        self::isRequested('business_type', $request) && array_push($load, 'business_type.translations');

        return $load;
    }

    public static function isRequested(string $key, $request = null) {
        return api_dependency_requested($key, $request);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array
     */
    public function toArray($request): array
    {
        $organization = $this->resource;
        $ownerData = [];

        if (Gate::allows('organizations.update', $organization)) {
            $ownerData = $organization->only([
                'iban', 'btw', 'phone', 'email', 'website', 'email_public',
                'phone_public', 'website_public'
            ]);
        }

        // $logoDep = api_dependency_requested('logo', $request);
        $fundsDep = api_dependency_requested('funds', $request, false);
        $fundsCountDep = api_dependency_requested('funds_count', $request, false);
        $businessType = api_dependency_requested('business_type', $request, true);
        $permissionsCountDep = api_dependency_requested('permissions', $request, true);

        $privateData = [
            'email' => $organization->email_public ? $organization->email ?? null: null,
            'phone' => $organization->phone_public ? $organization->phone ?? null: null,
            'website' => $organization->website_public ? $organization->website ?? null: null,
        ];
        
        return array_filter(array_merge($organization->only([
            'id', 'identity_address', 'name', 'kvk', 'business_type_id', 'tags',
            'email_public', 'phone_public', 'website_public',
            'is_sponsor', 'is_provider', 'is_validator',
            'validator_auto_accept_funds', 'description', 'description_html',
            'manage_provider_products',
        ]), $privateData,
            $ownerData, [
            'logo' => !self::isRequested('logo') ? '_null_' : new MediaResource($organization->logo),
            'business_type' => $businessType ? new BusinessTypeResource(
                $organization->business_type
            ) : '_null_',
            'funds' => $fundsDep ? $organization->funds->map(static function(Fund $fund) {
                return $fund->only([
                    'id', 'name'
                ]);
            }) : '_null_',
            'funds_count' => $fundsCountDep ? $organization->funds_count : '_null_',
            'permissions' => $permissionsCountDep ? $organization->identityPermissions(
                auth()->id()
            )->pluck('key') : '_null_',
        ]), static function($item) {
            return $item !== '_null_';
        });
    }
}
