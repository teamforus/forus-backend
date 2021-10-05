<?php

namespace App\Notifications\Organizations\PhysicalCardRequest;

/**
 * Class PhysicalCardRequestCreatedSponsorNotification
 */
class PhysicalCardRequestCreatedSponsorNotification extends BasePhysicalCardRequestNotification
{
    protected $key = 'notifications_physical_card_requests.physical_card_request_created';

    protected static $permissions = [
        'manage_vouchers',
    ];
}