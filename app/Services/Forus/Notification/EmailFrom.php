<?php

namespace App\Services\Forus\Notification;

use App\Models\Implementation;

/**
 * Class ImplementationFrom
 * @package App\Services\Forus\Notification
 */
class EmailFrom
{
    private $email_from_name;
    private $email_from_address;

    private $implementation_key;
    private $informal_communication;

    /**
     * EmailSender constructor.
     * @param Implementation $implementation
     */
    public function __construct(Implementation $implementation) {
        $this->email_from_name = $implementation->email_from_name ?: config('mail.from.name');
        $this->email_from_address = $implementation->email_from_address ?: config('mail.from.address');

        $this->implementation_key = $implementation->key ?: $implementation::KEY_GENERAL;
        $this->informal_communication = $implementation->informal_communication ?? false;
    }

    /**
     * @return string
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
     * @return mixed|string
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