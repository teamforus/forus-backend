<?php

namespace App\Notifications\Organizations\Funds;

class FundArchivedNotification extends BaseFundsNotification {
    protected static ?string $key = 'notifications_funds.created';
    protected static string|array $permissions = 'view_funds';
}
