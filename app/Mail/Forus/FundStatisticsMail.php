<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class FundStatisticsMail
 * @package App\Mail\Vouchers
 */
class FundStatisticsMail extends ImplementationMail
{
    protected $subjectKey = 'mails/system_mails.fund_statistics.title';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('fund_statistics');
    }
}
