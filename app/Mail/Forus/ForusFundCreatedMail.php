<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundCreatedMail
 * @package App\Mail\Funds\Forus
 */
class ForusFundCreatedMail extends ImplementationMail
{
    protected $subjectKey = 'mails/system_mails.forus_fund_created.title';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('forus_fund_created');
    }
}
