<?php

namespace App\Notifications\Identities\Voucher;

use App\Mail\Vouchers\PaymentSuccessBudgetMail;
use App\Models\Voucher;
use App\Models\Identity;
use App\Models\VoucherTransaction;
use Illuminate\Support\Arr;

/**
 * New budget voucher transaction created
 */
class IdentityVoucherBudgetTransactionNotification extends BaseIdentityVoucherNotification
{
    protected static ?string $key = 'notifications_identities.voucher_budget_transaction';
    protected static ?string $pushKey = "voucher.transaction";

    /**
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void
    {
        /** @var Voucher $voucher */
        $voucher = $this->eventLog->loggable;
        $target = Arr::get($this->eventLog->data, 'voucher_transaction_target');

        if ($target !== VoucherTransaction::TARGET_PROVIDER) {
            return;
        }

        $mailable = new PaymentSuccessBudgetMail([
            ...$this->eventLog->data,
            'webshop_link' => $voucher->fund->urlWebshop(),
        ], $voucher->fund->fund_config->implementation->getEmailFrom());

        $this->sendMailNotification($identity->email, $mailable, $this->eventLog);
    }
}
