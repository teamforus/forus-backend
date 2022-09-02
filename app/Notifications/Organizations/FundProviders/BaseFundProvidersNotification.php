<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Models\FundProvider;
use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

abstract class BaseFundProvidersNotification extends BaseOrganizationNotification
{
    protected static ?string $scope = self::SCOPE_PROVIDER;

    /**
     * @param FundProvider $loggable
     * @return \App\Models\Organization
     */
    public static function getOrganization(mixed $loggable): Organization
    {
        return $loggable->organization;
    }
}
