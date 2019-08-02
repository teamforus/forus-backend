<?php

namespace App\Mail\Validations;

use App\Mail\ImplementationMail;

class AddedAsValidator extends ImplementationMail
{
    private $sponsorName;

    public function __construct(
        string $email,
        string $sponsor_name,
        ?string $identityId
    ) {
        parent::__construct($email, $identityId);

        $this->sponsorName = $sponsor_name;
    }
    public function build(): ImplementationMail
    {
        return $this
        ->from(config('forus.mail.from.no-reply'))
        ->to($this->email)
        ->subject(trans('mails.validations.you_added_as_validator.title'))
        ->view('emails.validations.you_added_as_validator', [
            'sponsor_name' => $this->sponsorName,
            'implementation' => $this->getImplementation()
        ]);
    }
}
