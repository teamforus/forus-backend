<?php

namespace App\Services\Forus\Notification;

use App\Models\Implementation;
use Illuminate\Support\Facades\Config;

class EmailFrom
{
    private ?string $email_from_name;
    private ?string $email_from_address;

    private ?string $implementation_key;
    private ?bool $informal_communication;

    /**
     * EmailSender constructor.
     * @param Implementation $implementation
     */
    public function __construct(Implementation $implementation)
    {
        $this->email_from_name = $implementation->email_from_name ?: Config::get('mail.from.name');
        $this->email_from_address = $implementation->email_from_address ?: Config::get('mail.from.address');

        $this->implementation_key = $implementation->key ?: $implementation::KEY_GENERAL;
        $this->informal_communication = $implementation->informal_communication ?? false;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email_from_address;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->email_from_name;
    }

    /**
     * @return bool
     */
    public function isInformalCommunication(): bool
    {
        return $this->informal_communication;
    }

    /**
     * @return string
     */
    public function getImplementationKey(): string
    {
        return $this->implementation_key;
    }

    /**
     * @return EmailFrom
     */
    public static function createDefault(): EmailFrom
    {
        return new self(Implementation::general());
    }
}
