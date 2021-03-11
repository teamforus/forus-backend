<?php

namespace App\Services\Forus\Notification;

use App\Models\Implementation;

/**
 * Class ImplementationFrom
 * @package App\Services\Forus\Notification
 */
class EmailFrom
{
    private $implementation;

    /**
     * EmailSender constructor.
     * @param Implementation $implementation
     */
    public function __construct(Implementation $implementation) {
        $this->implementation = $implementation;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->implementation->email_from_address ?: config('mail.from.address');
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->implementation->email_from_name ?: config('mail.from.name');
    }

    /**
     * @return bool
     */
    public function isInformalCommunication(): bool
    {
        return $this->implementation->informal_communication;
    }

    /**
     * @return Implementation
     */
    public function getImplementation(): Implementation
    {
        return $this->implementation;
    }

    /**
     * @return EmailFrom
     */
    public static function createDefault(): EmailFrom
    {
        return new self(Implementation::general());
    }
}