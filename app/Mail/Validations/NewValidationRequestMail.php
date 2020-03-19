<?php

namespace App\Mail\Validations;

use App\Mail\ImplementationMail;
use App\Services\Forus\Notification\EmailFrom;

/**
 * Class NewValidationRequestMail
 * @package App\Mail\Validations
 */
class NewValidationRequestMail extends ImplementationMail
{
    private $link;

    public function __construct(
        string $link,
        ?EmailFrom $emailFrom
    ) {
        parent::__construct($emailFrom);

        $this->link = $link;
    }
    public function build(): ImplementationMail
    {
        return parent::build()
            ->subject(mail_trans('new_validation_request.title'))
            ->view('emails.validations.new_validation_request', [
                'validator_dashboard_link' => $this->link
            ]);
    }
}
