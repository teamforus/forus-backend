<?php

namespace App\Mail;

use App\Services\Forus\Notification\EmailFrom;
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

    protected $emailFrom;

    /**
     * ImplementationMail constructor.
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(
        ?EmailFrom $emailFrom
    ) {
        $this->emailFrom = $emailFrom;
    }

    /**
     * @return ImplementationMail
     */
    public function build(): ImplementationMail
    {
        return $this->from(
            $this->emailFrom->getEmail(),
            $this->emailFrom->getName()
        );
    }
}
