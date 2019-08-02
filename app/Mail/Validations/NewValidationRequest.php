<?php

namespace App\Mail\Validations;

use App\Mail\ImplementationMail;

class NewValidationRequest extends ImplementationMail
{
    private $link;

    public function __construct(
        string $email,
        string $link,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->link = $link;
    }
    public function build(): ImplementationMail
    {
        return $this
        ->from(config('forus.mail.from.no-reply'))
        ->to($this->email)
        ->subject(trans('mails.validations.new_validation_request.title'))
        ->view('emails.validations.new_validation_request', [
            'validator_dashboard_link' => $this->link,
            'implementation' => $this->getImplementation()
        ]);
    }
}
