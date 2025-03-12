<?php

namespace App\Services\Forus\Notification\Interfaces;

use Exception;

interface INotificationRepo
{
    /**
     * Is email unsubscribed from all emails.
     *
     * @param string $email
     * @return bool
     */
    public function isEmailUnsubscribed(string $email): bool;

    /**
     * Check if Mail class can be unsubscribed.
     * @param string $emailClass
     * @return bool
     */
    public function isMailUnsubscribable(string $emailClass): bool;

    /**
     * Check if Push notification can be unsubscribed.
     * @param string $pushKey
     * @return bool
     */
    public function isPushNotificationUnsubscribable(string $pushKey): bool;

    /**
     * Check if Push notification can be unsubscribed.
     *
     * @param string $identity_address
     * @param string $pushKey
     * @return bool
     */
    public function isPushNotificationUnsubscribed(string $identity_address, string $pushKey): bool;

    /**
     * Is email unsubscribed for specific email.
     *
     * @param string $identity_address
     * @param string $emailClass
     * @throws Exception
     * @return bool
     */
    public function isEmailTypeUnsubscribed($identity_address, $emailClass): bool;

    /**
     * Create new unsubscription from all emails link.
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeUnsubLink(string $email, string $token = null): string;

    /**
     * Create new unsubscription from all emails link.
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeReSubLink(string $email, string $token = null): string;

    /**
     * Unsubscribe email from all notifications.
     * @param string $email
     */
    public function unsubscribeEmail(string $email): void;

    /**
     * Remove email unsubscription from all notifications.
     * @param string $email
     */
    public function reSubscribeEmail(string $email): void;

    /**
     * @param string $token
     * @param bool $active
     * @return string|null
     */
    public function emailByUnsubscribeToken(string $token, bool $active = true): ?string;

    /**
     * @param string $identityAddress
     * @return array
     */
    public function getNotificationPreferences(string $identityAddress): array;

    /**
     * @param string $identityAddress
     * @param array $data
     * @return array
     */
    public function updateIdentityPreferences(string $identityAddress, array $data): array;

    /**
     * @return array
     */
    public function mailTypeKeys(): array;

    /**
     * @return array
     */
    public function pushNotificationTypeKeys(): array;

    /**
     * @return array
     */
    public function allPreferenceKeys(): array;
}
