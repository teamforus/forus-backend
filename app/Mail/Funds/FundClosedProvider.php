<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Mail\Mailable;

/**
 * Class FundClosed
 * @package App\Mail\Funds
 */
class FundClosedProvider extends ImplementationMail
{
    private $fundName;
    private $fundEndDate;
    private $fundStartDate;
    private $providerName;
    private $link;

    /**
     * Create a new message instance.
     *
     * FundClosedProvider constructor.
     * @param $fundName
     * @param $fundStartDate
     * @param $fundEndDate
     * @param $providerName
     * @param $link
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        $fundName,
        $fundStartDate,
        $fundEndDate,
        $providerName,
        $link,
        ?EmailFrom $emailFrom
    ) {
        $this->setMailFrom($emailFrom);

        $this->fundName     = $fundName;
        $this->fundEndDate  = $fundEndDate;
        $this->fundStartDate  = $fundStartDate;
        $this->providerName = $providerName;
        $this->link         = $link;
    }

    public function build(): Mailable
    {
        return $this->buildBase()
            ->subject(mail_trans('fund_closed_provider.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.funds.fund_closed_provider', [
                'fund_name'         => $this->fundName,
                'fund_start_date'   => $this->fundStartDate,
                'fund_end_date'     => $this->fundEndDate,
                'provider_name'     => $this->providerName,
                'dashboard_link'    => $this->link
            ]);
    }
}

