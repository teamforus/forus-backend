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

    public $emailFrom;
    public $implementationKey;
    public $informalCommunication;
    public $communicationType;

    protected $mailData = [];

    /**
     * @param EmailFrom|null $emailFrom
     */
    public function setMailFrom(?EmailFrom $emailFrom): void {
        $this->emailFrom = $emailFrom;
        $this->implementationKey = $emailFrom->getImplementationKey() ?: null;
        $this->informalCommunication = $emailFrom->isInformalCommunication();
        $this->communicationType =  $this->informalCommunication ? 'informal' : 'formal';
    }

    /**
     * @return Mailable
     */
    public function buildBase(): Mailable
    {
        return $this->from($this->emailFrom->getEmail(), $this->emailFrom->getName());
    }

    /**
     * @return string
     */
    protected function getSubject(): string {
        return config('app.name');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function escapeData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (!ends_with($key, '_html')) {
                $data[$key] = e($value);
            }
        }

        return $data;
    }
}
