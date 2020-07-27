<?php

namespace App\Notifications\Organizations\FundProviders;

use App\Models\FundProvider;
use App\Models\Organization;
use App\Notifications\Organizations\BaseOrganizationNotification;

/**
 * Class BaseFundProvidersNotification
 * @package App\Notifications\Organizations\FundProviders
 */
abstract class BaseFundProvidersNotification extends BaseOrganizationNotification
{
    /**
     * @var string
     */
    protected $scope = self::SCOPE_PROVIDER;

    /**
     * @param \Illuminate\Database\Eloquent\Model|FundProvider $loggable
     * @return \App\Models\Organization|void
     */
    public static function getOrganization($loggable): Organization
    {
        return $loggable->organization;
    }
}
