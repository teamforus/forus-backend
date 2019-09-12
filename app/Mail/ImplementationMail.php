<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class ImplementationMail
 * @property string $email Destination email
 * @property string|null $identityId Destination email
 * @package App\Mail
 */
class ImplementationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $email = [];
    private $identityId;

    /**
     * ImplementationMail constructor.
     * @param $email
     * @param string|null $identityId
     */
    public function __construct($email, ?string $identityId)
    {
        $this->email = $email;
        $this->identityId = $identityId;
    }

    /**
     * @return ImplementationMail
     */
    public function build(): ImplementationMail
    {
        return $this->to(
            $this->email
        )->from(
            config('mail.from.address'),
            config('mail.from.name')
        );
    }
}
