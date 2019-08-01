<?php

namespace App\Mail\Validations;

use App\Mail\ImplementationMail;

class NewValidationRequest extends ImplementationMail
{
    public function __construct(
        string $email,
        string $validator_dashboard_link,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->validator_dashboard_link   = $validator_dashboard_link;
    }
    public function build(): Mailable
    {
        return $this
        ->from(config('forus.mail.from.no-reply'))
        ->to($this->email)
        ->subject(trans())
        ->view('emails.validations.new_validation_request', [
            'validator_dashboard_link'              => $this->validator_dashboard_link,
            'implementation' => $this->getImplementation()
        ]);
    }
}
