<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;

/**
 * Class FundStatisticsMail
 * @package App\Mail\Vouchers
 */
class FundStatisticsMail extends ImplementationMail
{
    protected $subjectKey = 'mails/fund_statistics.title';
    protected $viewKey = 'emails.forus.fund_statistics';
}
