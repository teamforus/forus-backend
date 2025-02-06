<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ForusFundCreatedMail extends ImplementationMail
{
    public $subject = 'Er is een nieuw fonds toegevoegd: :fund_name';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('forus_fund_created');
    }
}
