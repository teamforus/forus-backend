<?php

namespace App\Notifications\Organizations\Products;

use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

abstract class BaseProductsNotification extends BaseOrganizationNotification
{
    protected static ?string $scope = self::SCOPE_PROVIDER;

    /**
     * @param \App\Models\Product $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization(mixed $loggable): Organization
    {
        return $loggable->organization;
    }
}
