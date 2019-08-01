<?php


namespace App\Mail;


use App\Models\Implementation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImplementationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var object|array|string $to
     */
    public $email = [];

    /**
     * @var string|null $identityId
     */
    private $identityId;

    public function __construct($email, ?string $identityId)
    {
        $this->email = $email;
        $this->identityId = $identityId;
    }

    protected function getImplementation(): ?array
    {
        $implementation = Implementation::activeKey();

        return flatten_by_key(
            config("forus.mails.implementations.$implementation")
        );
    }
}
