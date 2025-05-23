<?php

namespace App\Notifications\Organizations\PhysicalCardRequest;

/**
 * Class PhysicalCardRequestCreatedSponsorNotification.
 */
class PhysicalCardRequestCreatedSponsorNotification extends BasePhysicalCardRequestNotification
{
    protected static ?string $key = 'notifications_physical_card_requests.physical_card_request_created';
    protected static string|array $permissions = 'manage_vouchers';
}
