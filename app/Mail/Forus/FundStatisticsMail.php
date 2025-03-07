<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class FundStatisticsMail extends ImplementationMail
{
    public $subject = 'Totaal aantal gebruikers :sponsor_name - :fund_name';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('fund_statistics');
    }
}
