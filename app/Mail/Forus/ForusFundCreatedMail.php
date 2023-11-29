<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

/**
 * Class FundCreatedMail
 * @package App\Mail\Funds\Forus
 */
class ForusFundCreatedMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.forus_fund_created.title';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('forus_fund_created');
    }
}
