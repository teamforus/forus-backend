<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\SendProductVoucherBySponsorMail;
use App\Models\Identity;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Send voucher to email.
 */
class IdentitySponsorProductVoucherSharedByEmailNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.sponsor_product_voucher_shared_by_email';
    protected static ?string $scope = null;

    /**
     * Get identities which are eligible for the notification.
     *
     * @param Voucher $loggable
     * @param EventLog $eventLog
     * @return \Illuminate\Support\Collection
     */
    public static function eligibleIdentities($loggable, EventLog $eventLog): Collection
    {
        if (!$loggable->identity_id && Arr::get($eventLog->data, 'email')) {
            return collect([Identity::findByEmail(Arr::get($eventLog->data, 'email'))]);
        }

        return Identity::where('id', $loggable->identity_id)->get();
    }

    /**
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;

        $mailable = new SendProductVoucherBySponsorMail(
            $this->eventLog->data,
            $voucher->fund->fund_config->implementation->getEmailFrom()
        );

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
