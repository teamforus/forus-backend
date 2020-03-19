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

    /**
     * EmailSender constructor.
     * @param string $email
     * @param string|null $name
     */
    public function __construct(string $email, string $name = null)
    {
        $this->email = $email;
        $this->name = $name;
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

    public static function createDefault()
    {
        return new self(config('mail.from.address'), config('mail.from.name'));
    }
}