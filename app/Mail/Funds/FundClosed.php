<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;

/**
 * Class FundClosed
 * @package App\Mail\Funds
 */
class FundClosed extends ImplementationMail
{
    private $fundName;
    private $fundEndDate;
    private $fundContact;
    private $sponsorName;
    private $link;

    /**
     * Create a new message instance.
     *
     * FundClosed constructor.
     * @param $fundName
     * @param $fundEndDate
     * @param $fundContact
     * @param $sponsor_name
     * @param $link
     */
    public function __construct($fundName, $fundEndDate, $fundContact, $sponsor_name, $link)
    {
        parent::__construct();

        $this->fundName     = $fundName;
        $this->link         = $link;
        $this->fundEndDate  = $fundEndDate;
        $this->fundContact  = $fundContact;
        $this->sponsorName  = $sponsor_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('fund_closed.title', [
                'fund_name' => $this->fundName
            ]))
            ->view('emails.funds.fund_closed', [
                'sponsor_name'  => $this->sponsorName,
                'fund_name'     => $this->fundName,
                'fund_end_date' => $this->fundEndDate,
                'fund_contact'  => $this->fundContact,
                'webshop_link'  => $this->link
            ]);
    }
}
