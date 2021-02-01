<?php

namespace App\Services\Forus\Notification;

/**
 * Class ImplementationFrom
 * @package App\Services\Forus\Notification
 */
class EmailFrom
{
    private $email;
    private $name;
    private $informalCommunication;

    /**
     * EmailSender constructor.
     * @param string $email
     * @param string|null $name
     * @param bool $informalCommunication
     */
    public function __construct(
        string $email,
        string $name = null,
        bool $informalCommunication = false
    ) {
        $this->email = $email;
        $this->name = $name;
        $this->informalCommunication = $informalCommunication;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isInformalCommunication(): bool
    {
        return $this->informalCommunication;
    }

    /**
     * @return EmailFrom
     */
    public static function createDefault(): EmailFrom
    {
        return new self(config('mail.from.address'), config('mail.from.name'));
    }
}