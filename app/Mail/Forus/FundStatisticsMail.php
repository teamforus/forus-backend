<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

/**
 * Class FundStatisticsMail
 * @package App\Mail\Vouchers
 */
class FundStatisticsMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.fund_statistics.title';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('fund_statistics');
    }
}
