<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

class TransactionVerifyMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.forus_transaction_verify.title';

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('forus_transaction_verify');
    }
}
