<?php

namespace App\Services\Forus\Notification\Repositories;

use App\Mail\Auth\UserLoginMail;
use App\Mail\Digest\DigestProviderFundsMail;
use App\Mail\Digest\DigestProviderProductsMail;
use App\Mail\Digest\DigestRequesterMail;
use App\Mail\Digest\DigestSponsorMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\FundExpiredMail;
use App\Mail\Funds\FundStartedMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\Funds\ProviderRejectedMail;
use App\Mail\User\EmailActivationMail;
use App\Mail\Vouchers\PaymentSuccessMail;
use App\Mail\Vouchers\ProductReservedMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Services\Forus\Notification\Models\NotificationPreference;
use App\Services\Forus\Notification\Models\NotificationUnsubscription;
use App\Services\Forus\Notification\Models\NotificationUnsubscriptionToken;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use Illuminate\Support\Collection;

/**
 * Class NotificationServiceRepo
 * @package App\Services\Forus\Notification\Repositories
 */
class NotificationRepo implements INotificationRepo
{
    /**
     * Map between type keys and Mail classes
     * @var array
     */
    protected static $mailMap = [
        // User generated emails
        'vouchers.send_voucher' => SendVoucherMail::class,
        'vouchers.share_voucher' => ShareProductVoucherMail::class,
        'vouchers.payment_success' => PaymentSuccessMail::class,

        // Mails for sponsors/providers
        'funds.new_fund_started' => FundStartedMail::class,
        'funds.provider_applied' => ProviderAppliedMail::class,
        'funds.provider_approved' => ProviderApprovedMail::class,
        'funds.provider_rejected' => ProviderRejectedMail::class,
        'funds.product_sold_out' => ProductSoldOutMail::class,
        'funds.product_reserved' => ProductReservedMail::class,
        'funds.fund_expires' => FundExpiredMail::class,
        'funds.balance_warning' => FundBalanceWarningMail::class,

        // Authorization emails
        'auth.user_login' => UserLoginMail::class,
        'auth.email_activation' => EmailActivationMail::class,

        // Digests
        'digest.daily_sponsor' => DigestSponsorMail::class,
        'digest.daily_validator' => DigestRequesterMail::class,
        'digest.daily_requester' => DigestRequesterMail::class,
        'digest.daily_provider_funds' => DigestProviderFundsMail::class,
        'digest.daily_provider_products' => DigestProviderProductsMail::class,
    ];

    /**
     * Map between type keys and Mail classes
     * @var array
     */
    protected static $pushNotificationMap = [
        'voucher.assigned',
        'voucher.transaction',
        'employee.created',
        'employee.deleted',
        'bunq.transaction_success',
        'funds.provider_approved',
    ];

    /**
     * Emails that you can't unsubscribe from
     * @var array
     */
    protected static $mandatoryEmail = [
        'auth.user_login', 'auth.email_activation', 'vouchers.share_voucher',
        'vouchers.send_voucher', 'funds.balance_warning',
    ];

    /**
     * @return array
     */
    public static function getMailMap(): array
    {
        return self::$mailMap;
    }

    /**
     * @return array
     */
    public static function getPushNotificationMap(): array
    {
        return self::$pushNotificationMap;
    }

    /**
     * @return array
     */
    public static function getMandatoryMailKeys(): array
    {
        return self::$mandatoryEmail;
    }

    /**
     * Is email unsubscribed from all emails
     * @param string $email
     * @return bool
     */
    public function isEmailUnsubscribed(string $email): bool {
        return NotificationUnsubscription::where(
            compact('email'))->count() > 0;
    }

    /**
     * Check if Mail class can be unsubscribed
     * @param string $emailClass
     * @return bool
     */
    public function isMailUnsubscribable(string $emailClass): bool {
        $keys = array_flip(self::getMailMap());

        if (!isset($keys[$emailClass])) {
            return false;
        }

        return !in_array($keys[$emailClass], self::getMandatoryMailKeys());
    }

    /**
     * Check if Push notification can be unsubscribed
     * @param string $key
     * @return bool
     */
    public function isPushNotificationUnsubscribable(string $key): bool {
        return in_array($key, self::getPushNotificationMap());
    }

    /**
     * Check if Push notification can be unsubscribed
     * @param string $identity_address
     * @param string $key
     * @return bool
     */
    public function isPushNotificationUnsubscribed(
        string $identity_address,
        string $key
    ): bool {
        $subscribed = false;
        $type = 'push';

        return NotificationPreference::where(compact(
            'identity_address', 'key', 'subscribed', 'type'
        ))->count() > 0;
    }

    /**
     * Is email $emailClass unsubscribed
     * @param string $identity_address
     * @param string $emailClass
     * @return bool
     * @throws \Exception
     */
    public function isEmailTypeUnsubscribed(
        $identity_address,
        $emailClass
    ): bool {
        if (!$this->isMailUnsubscribable($emailClass)) {
            return false;
        }

        $key = array_flip(self::getMailMap())[$emailClass];
        $type = 'email';
        $subscribed = false;

        return NotificationPreference::where(compact(
            'identity_address', 'key', 'subscribed', 'type'
            ))->count() > 0;
    }

    /**
     * Create new unsubscription from all emails link
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeUnsubLink(string $email, string $token = null): string {
        return url(sprintf(
            '/notifications/unsubscribe/%s',
            $this->makeToken($email, $token)
        ));
    }

    /**
     * Create new unsubscription from all emails link
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeReSubLink(string $email, string $token = null): string {
        return url(sprintf(
            '/notifications/subscribe/%s',
            $this->makeToken($email, $token)
        ));
    }

    /**
     * Try to reuse existing token or create new one
     * @param string $email
     * @param string|null $token
     * @return string
     */
    private function makeToken(string $email, string $token = null) {
        $model = $token ? NotificationUnsubscriptionToken::findByToken($token) : null;

        return ($model ?: NotificationUnsubscriptionToken::makeToken($email))->token;
    }

    /**
     * Unsubscribe email from all notifications
     * @param string $email
     */
    public function unsubscribeEmail(
        string $email
    ): void {
        NotificationUnsubscription::firstOrCreate(
            compact('email')
        );
    }

    /**
     * Remove email unsubscription from all notifications
     * @param string $email
     */
    public function reSubscribeEmail(
        string $email
    ): void {
        NotificationUnsubscription::where(compact('email'))->delete();
    }

    /**
     * @param string $token
     * @param bool $active
     * @return string|null
     */
    public function emailByUnsubscribeToken(
        string $token,
        bool $active = true
    ): ?string {
        return NotificationUnsubscriptionToken::findByToken($token, $active)->email ?? null;
    }

    /**
     * @param string $identityAddress
     * @return Collection
     */
    public function getNotificationPreferences(
        string $identityAddress
    ): Collection {
        $subscribed = false;
        $identity_address = $identityAddress;

        $mailKeys = collect();
        foreach ($this->mailTypeKeys() as $mailKey) {
            $mailKeys->push((object)[
                'value' => $mailKey,
                'type'  => 'email'
            ]);
        }

        $pushKeys = collect();
        foreach (self::getPushNotificationMap() as $pushKey) {
            $pushKeys->push((object)[
                'value' => $pushKey,
                'type'  => 'push'
            ]);
        }

        $keys = $mailKeys->merge($pushKeys);

        $unsubscribedKeys = NotificationPreference::where(compact(
            'identity_address', 'subscribed'
        ))->pluck('key')->values();

        return $keys->map(static function($key) use ($unsubscribedKeys) {
            return [
                'key'  => $key->value,
                'type' => $key->type,
                'subscribed' => $unsubscribedKeys->search($key->value) === false
            ];
        })->values();
    }

    /**
     * @param string $identityAddress
     * @param array $data
     * @return Collection
     */
    public function updateIdentityPreferences(
        string $identityAddress,
        array $data
    ): Collection {
        $data_keys = array_keys(array_pluck($data, 'subscribed', 'key'));
        $preference_keys = $this->allPreferenceKeys();

        foreach ($data as $setting) {
            if (array_intersect($preference_keys, $data_keys)) {
                NotificationPreference::firstOrCreate([
                    'identity_address'  => $identityAddress,
                    'key'               => $setting['key'],
                    'type'              => $setting['type'],
                ])->update([
                    'subscribed'        => $setting['subscribed']
                ]);
            }
        }

        return $this->getNotificationPreferences($identityAddress);
    }

    /**
     * @return array
     */
    public function mailTypeKeys(): array
    {
        return array_values(array_diff(
            array_keys(self::getMailMap()),
            self::getMandatoryMailKeys()
        ));
    }

    /**
     * @return array
     */
    public function pushNotificationTypeKeys() : array {
        return self::getPushNotificationMap();
    }

    /**
     * @return array
     */
    public function allPreferenceKeys(): array {
        return array_merge($this->mailTypeKeys(), $this->pushNotificationTypeKeys());
    }
}