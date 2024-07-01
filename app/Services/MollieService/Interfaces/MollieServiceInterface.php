<?php

namespace App\Services\MollieService\Interfaces;

use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\Objects\Profile;
use App\Services\MollieService\Objects\Payment;
use App\Services\MollieService\Objects\Refund;
use App\Services\MollieService\Objects\Organization;
use Illuminate\Support\Collection;

interface MollieServiceInterface
{
    public const PAYMENT_METHOD_IDEAL = 'ideal';

    /**
     * @param MollieToken $mollieToken
     * @return static
     */
    public static function make(MollieToken $mollieToken): static;

    /**
     * @param string $state
     * @return string
     */
    public function mollieConnect(string $state): string;

    /**
     * @param string $code
     * @param string $state
     * @return MollieConnection|null
     */
    public function exchangeOauthCode(string $code, string $state): ?MollieConnection;

    /**
     * @param string $state
     * @param string $name
     * @param array $owner
     * @param array $address
     * @return string
     */
    public function createClientLink(
        string $state,
        string $name,
        array $owner = [],
        array $address = [],
    ): string;

    /**
     * @return Organization
     */
    public function getOrganization(): Organization;

    /**
     * @return string
     */
    public function getOnboardingState(): string;

    /**
     * @param array $attributes
     * @return Profile
     */
    public function createProfile(array $attributes = []): Profile;

    /**
     * @param string $profileId
     * @return Profile
     */
    public function readProfile(string $profileId): Profile;

    /**
     * @param string $profileId
     * @param array $attributes
     * @return Profile
     */
    public function updateProfile(string $profileId, array $attributes = []): Profile;

    /**
     * @return Collection
     */
    public function readAllProfiles(): Collection;

    /**
     * @param string $profileId
     * @return Collection
     */
    public function readAllPaymentMethods(string $profileId): Collection;

    /**
     * @param string $profileId
     * @return Collection
     */
    public function readActivePaymentMethods(string $profileId): Collection;

    /**
     * @param string $profileId
     * @param string $method
     * @return bool
     */
    public function enablePaymentMethod(string $profileId, string $method): bool;

    /**
     * @param string $profileId
     * @param string $method
     * @return bool
     */
    public function disablePaymentMethod(string $profileId, string $method): bool;

    /**
     * @param string $profileId
     * @param array $attributes
     * @return Payment
     */
    public function createPayment(string $profileId, array $attributes): Payment;

    /**
     * @param string $paymentId
     * @return Payment
     */
    public function getPayment(string $paymentId): Payment;

    /**
     * @param string $paymentId
     * @return Payment
     */
    public function cancelPayment(string $paymentId): Payment;

    /**
     * @param string $paymentId
     * @param array $attributes
     * @return Refund
     */
    public function refundPayment(string $paymentId, array $attributes): Refund;

    /**
     * @param string $paymentId
     * @return Collection
     */
    public function getPaymentRefunds(string $paymentId): Collection;

    /**
     * @return bool
     */
    public function revokeToken(): bool;
}