<?php

namespace App\Mail\Validations;

use App\Mail\ImplementationMail;

/**
 * Class NewValidationRequestMail
 * @package App\Mail\Validations
 */
class NewValidationRequestMail extends ImplementationMail
{
    private $link;

    public function __construct(
        string $link,
        ?string $identityId
    ) {
        parent::__construct($identityId);

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
