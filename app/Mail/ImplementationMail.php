<?php


namespace App\Mail;


use App\Models\Implementation;
use App\Services\Forus\Identity\Repositories\IdentityRepo;
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
    public $identityId;

    /**
     * @var IdentityRepo $identityRepo
     */
    private $identityRepo;

    /**
     * @var string $notYouLink
     */
    public $notYouLink;

    /**
     * @var $emailPreferencesLink
     */
    public $emailPreferencesLink;

    public function __construct($email, ?string $identityId)
    {
        $this->email = $email;
        $this->identityId = $identityId;

        $this->identityRepo = resolve('forus.services.identity');

        $this->notYouLink = $this->createNotYouLink();
        $this->emailPreferencesLink = $this->createEmailPreferencesLink();
    }

    private function getUrlForImplementation(): string
    {
        $data = Implementation::byKey(Implementation::activeKey());

        return $data['url_webshop'] ?? config('forus.front_ends.webshop');
    }

    private function createNotYouLink(): string
    {
        $identityProxy = $this->identityRepo->makeProxy(
            'email_preferences_code',
            $this->identityId
        );

        return sprintf('%s/email/unsubscribe/%s/%s',
            $this->getUrlForImplementation(),
            $this->identityId,
            $identityProxy['exchange_token']
        );
    }

    private function createEmailPreferencesLink(): string
    {
        $identityProxy = $this->identityRepo->makeProxy(
            'email_preferences_code',
            $this->identityId
        );

        return sprintf('%s/email/preferences/%s/%s',
            $this->getUrlForImplementation(),
            $this->identityId,
            $identityProxy['exchange_token']
        );
    }
}
