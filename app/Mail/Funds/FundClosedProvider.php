<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

/**
 * Class FundClosed
 * @package App\Mail\Funds
 */
class FundClosedProvider extends ImplementationMail
{
    private $fundName;
    private $fundEndDate;
    private $providerName;
    private $link;

    /**
     * Create a new message instance.
     *
     * FundClosedProvider constructor.
     * @param $fundName
     * @param $fundEndDate
     * @param $providerName
     * @param $link
     */
    public function __construct($fundName, $fundEndDate, $providerName, $link)
    {
        parent::__construct();

        $this->fundName     = $fundName;
        $this->fundEndDate  = $fundEndDate;
        $this->providerName = $providerName;
        $this->link         = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('fund_closed_provider.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.funds.fund_closed_provider', [
                'fund_name'      => $this->fundName,
                'fund_end_date'  => $this->fundEndDate,
                'provider_name'  => $this->providerName,
                'dashboard_link' => $this->link
            ]);
    }
}

