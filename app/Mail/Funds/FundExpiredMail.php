<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class FundExpiredMail
 * @package App\Mail\Funds
 */
class FundExpiredMail extends ImplementationMail
{
    private $fundName;
    private $sponsorName;
    private $startDateFund;
    private $endDateFund;
    private $phoneNumberSponsor;
    private $emailAddressSponsor;
    private $shopImplementationUrl;

    public function __construct(
        string $fundName,
        string $sponsorName,
        $startDateFund,
        $endDateFund,
        string $phoneNumberSponsor,
        string $emailAddressSponsor,
        string $shopImplementationUrl,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->fundName = $fundName;
        $this->sponsorName = $sponsorName;
        $this->startDateFund = $startDateFund;
        $this->endDateFund = $endDateFund;
        $this->phoneNumberSponsor = $phoneNumberSponsor;
        $this->emailAddressSponsor = $emailAddressSponsor;
        $this->shopImplementationUrl = $shopImplementationUrl;
    }

    public function build(): Mailable
    {
        $data = [
            'fund_name' => $this->fundName,
            'sponsor_name' => $this->sponsorName,
            'start_date_fund' => $this->startDateFund,
            'end_date_fund' => $this->endDateFund,
            'phone_number_sponsor' => $this->phoneNumberSponsor,
            'email_address_sponsor' => $this->emailAddressSponsor,
            'shop_implementation_url' => $this->shopImplementationUrl
        ];

        return $this->buildBase()
            ->subject(mail_trans('fund_expires.title', $data))
            ->view('emails.funds.fund_expires', $data);
    }
}
