<?php


namespace App\Services\Forus\Notification\Interfaces;


use Illuminate\Support\Collection;

interface INotificationRepo
{
    /**
     * Is email unsubscribed from all emails
     *
     * @param string $email
     * @return bool
     */
    public function isEmailUnsubscribed(
        string $email
    ): bool;

    /**
     * Check if Mail class can be unsubscribed
     * @param string $emailClass
     * @return bool
     */
    public function isMailUnsubscribable(
        string $emailClass
    ): bool;

    /**
     * Is email unsubscribed for specific email
     *
     * @param string $identity_address
     * @param string $emailClass
     * @return bool
     * @throws \Exception
     */
    public function isEmailTypeUnsubscribed(
        $identity_address,
        $emailClass
    ): bool;

    /**
     * Create new unsubscription from all emails link
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeUnsubLink(
        string $email,
        string $token = null
    ): string;

    /**
     * Create new unsubscription from all emails link
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeReSubLink(
        string $email,
        string $token = null
    ): string;

    /**
     * Unsubscribe email from all notifications
     * @param string $email
     */
    public function unsubscribeEmail(
        string $email
    ): void;

    /**
     * Remove email unsubscription from all notifications
     * @param string $email
     */
    public function reSubscribeEmail(
        string $email
    ): void;

    /**
     * @param string $token
     * @param bool $active
     * @return string|null
     */
    public function emailByUnsubscribeToken(
        string $token,
        bool $active = true
    ): ?string;

    /**
     * @param string $identityAddress
     * @return Collection
     */
    public function getNotificationPreferences(
        string $identityAddress
    ): Collection;

    /**
     * @param string $identityAddress
     * @param array $data
     * @return Collection
     */
    public function updateIdentityPreferences(
        string $identityAddress,
        array $data
    ): Collection;

    /**
     * @return array
     */
    public function mailTypeKeys(): array;
}