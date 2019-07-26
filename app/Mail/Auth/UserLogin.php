<?php

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserLogin extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string|null $identityId
     */
    private $identityId;

    /**
     * @var string $link
     */
    private $link;

    /**
     * @var string $platform
     */
    private $platform;

    public function __construct(
        string $email,
        ?string $identityId,
        string $link,
        string $platform
    ) {
        $this->email = $email;
        $this->identityId = $identityId;
        $this->link = $link;
        $this->platform = $platform;
    }

    public function build(): Mailable
    {
        return $this
            ->from(config('forus.mail.from.no-reply'))
            ->to($this->email)
            ->subject('Inloggen op Forus')
            ->view('emails.login.login_via_email', [
                'platform' => $this->platform,
                'link' => $this->link
            ]);
    }
}
