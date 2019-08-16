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
            ->from(config('forus.mail.from.no-reply'), config('forus.mail.from.name'))
            ->to($this->email)
            ->subject(implementation_trans('new_validation_request.title'))
            ->view('emails.validations.new_validation_request', [
                'validator_dashboard_link' => $this->link
            ]);
    }
}
