<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;

/**
 * Class FundCreatedMail
 * @package App\Mail\Funds\Forus
 */
class ForusFundCreatedMail extends ImplementationMail
{
    protected $subjectKey = 'mails/forus/forus_fund_created.title';
    protected $viewKey = 'emails.forus.forus_fund_created';
}
