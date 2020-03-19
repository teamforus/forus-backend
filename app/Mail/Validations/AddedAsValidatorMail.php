<?php

namespace App\Mail\Validations;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

/**
 * Class AddedAsValidatorMail
 * @package App\Mail\Validations
 */
class AddedAsValidatorMail extends ImplementationMail
{
    private $sponsorName;

    public function __construct(
        string $sponsor_name,
        ?EmailFrom $emailFrom
    ) {
        parent::__construct($emailFrom);

        $this->sponsorName = $sponsor_name;
    }
    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('you_added_as_validator.title'))
            ->view('emails.validations.you_added_as_validator', [
                'sponsor_name' => $this->sponsorName
            ]);
    }
}
