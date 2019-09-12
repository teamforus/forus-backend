<?php

use App\Services\Forus\Notification\Models\NotificationType;

class NotificationTypesTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $notification_types = [
            // Validators
            'validations.new_validation_request',
            'validations.you_added_as_validator',

            // Mails for sponsors/providers
            'funds.fund_expires',
            'funds.product_added',
            'funds.balance_warning',
            'funds.provider_applied',
            'funds.provider_approved',
            'funds.provider_rejected',
            'funds.product_sold_out',
            'funds.product_reserved',
            'funds.new_fund_started',
            'funds.new_fund_created',
            'funds.new_fund_applicable',

            // Authorization emails
            'auth.user_login',
            'auth.email_activation',

            // Voucher related
            'vouchers.share_voucher',
            'vouchers.payment_success',
            'vouchers.send_voucher',
        ];

        foreach ($notification_types as $key) {
            NotificationType::create(compact('key'));
        }
    }
}
