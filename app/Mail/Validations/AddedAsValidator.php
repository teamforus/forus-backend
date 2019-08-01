<?php

namespace App\Mail\Validations;

use App\Mail\ImplementationMail;

class AddedAsValidator extends ImplementationMail
{
    public function __construct(
        string $email,
        string $sponsor_name,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->sponsor_name             = $sponsor_name;
    }
    public function build(): ImplementationMail
    {
        return $this
        ->from(config('forus.mail.from.no-reply'))
        ->to($this->email)
        ->subject(trans())
        ->view('emails.validations.you_added_as_validator', [
            'sponsor_name'              => $this->$sponsor_name,
            'implementation' => $this->getImplementation()
        ]);
    }
}
