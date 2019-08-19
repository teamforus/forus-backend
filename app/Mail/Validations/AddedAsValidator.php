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
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(mail_trans('you_added_as_validator.title'))
            ->view('emails.validations.you_added_as_validator', [
                'sponsor_name' => $this->sponsorName
            ]);
    }
}
