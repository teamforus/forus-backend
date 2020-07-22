<?php

namespace App\Notifications\Organizations\Products;

use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

/**
 * Class BaseProductsNotification
 * @package App\Notifications\Organizations\Products
 */
abstract class BaseProductsNotification extends BaseOrganizationNotification
{
    /**
     * @var string
     */
    protected $scope = self::SCOPE_PROVIDER;

    /**
     * @param \App\Models\Product $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization($loggable): Organization
    {
        return $loggable->organization;
    }
}
