<?php

namespace App\Mail\Forus;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class TransactionVerifyMail extends ImplementationMail
{
    public $subject = 'A new transaction to verify';

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('forus_transaction_verify');
    }
}
