<?php

namespace App\Mail\Vouchers;


use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

class AssignedVoucherMail extends ImplementationMail
{
    private $type;
    private $data;

    /**
     * Create a new message instance.
     *
     * AssignedVoucherMail constructor.
     * @param EmailFrom|null $emailFrom
     * @param string $type
     * @param array $data
     */
    public function __construct(
        ?EmailFrom $emailFrom,
        string $type,
        array $data = []
    ) {
        $this->type = $type;
        $this->data = $this->escapeData($data);
        $this->setMailFrom($emailFrom);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Mailable
    {
        switch ($this->type) {
            case 'budget': return $this->sendMailBudgetVoucher();
            case 'product': return $this->sendMailProductVoucher();
            case 'subsidies': return $this->sendMailBudgetSubsidies();
        }
    }

    /**
     * @return Mailable
     */
    private function sendMailBudgetVoucher(): Mailable {
        return $this->buildBase()->subject(
            mail_trans('voucher_assigned_budget.title', $this->data)
        )->view('emails.vouchers.voucher_assigned_budget', [
            'data' => $this->data
        ]);
    }

    /**
     * @return Mailable
     */
    private function sendMailProductVoucher(): Mailable {
        return $this->buildBase()->subject(
            mail_trans('voucher_assigned_product.title', $this->data)
        )->view('emails.vouchers.voucher_assigned_product', [
            'data' => $this->data
        ]);
    }

    /**
     * @return Mailable
     */
    private function sendMailBudgetSubsidies(): Mailable {
        $this->communicationType =  $this->emailFrom->isInformalCommunication() ? 'informal' : 'formal';

        return $this->buildBase()->subject(
            mail_trans('voucher_assigned_subsidy.title_' . $this->communicationType, $this->data)
        )->view('emails.vouchers.voucher_assigned_subsidy', [
            'data' => $this->data
        ]);
    }
}
