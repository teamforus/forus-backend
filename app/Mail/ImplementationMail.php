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

    private $identityRepo;
    public $identityId;

    /**
     * ImplementationMail constructor.
     * @param string|null $identityId
     */
    public function __construct(
        ?string $identityId
    ) {
        $this->identityRepo = resolve('forus.services.identity');
        $this->identityId = $identityId;
    }

    /**
     * @return ImplementationMail
     */
    public function build(): ImplementationMail
    {
        return $this->from(
            config('mail.from.address'),
            config('mail.from.name')
        );
    }
}
