<?php

namespace App\Services\Forus\Notification\Repositories;

use App\Mail\Auth\UserLoginMail;
use App\Mail\Digest\DigestProviderFundsMail;
use App\Mail\Digest\DigestProviderProductsMail;
use App\Mail\Digest\DigestRequesterMail;
use App\Mail\Digest\DigestSponsorMail;
use App\Mail\Digest\DigestSponsorProductUpdatesMail;
use App\Mail\Digest\DigestValidatorMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\User\EmailActivationMail;
use App\Mail\User\FundRequestAssignedBySupervisorMail;
use App\Mail\Vouchers\PaymentSuccessBudgetMail;
use App\Mail\Vouchers\ProductBoughtProviderMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Mail\Vouchers\VoucherExpireSoonBudgetMail;
use App\Models\SystemNotification;
use App\Notifications\Identities\Employee\IdentityAddedEmployeeNotification;
use App\Notifications\Identities\Employee\IdentityAssignedToFundRequestBySupervisorNotification;
use App\Notifications\Identities\Employee\IdentityChangedEmployeeRolesNotification;
use App\Notifications\Identities\Employee\IdentityRemovedEmployeeNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProviderApprovedBudgetNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProviderApprovedProductsNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestApprovedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestCreatedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDeniedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestDisregardedNotification;
use App\Notifications\Identities\FundRequest\IdentityFundRequestRecordFeedbackRequestedNotification;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationAcceptedNotification;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationCanceledNotification;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationCreatedNotification;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationRejectedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherAddedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherExpiredNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherReservedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherSharedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherTransactionNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAddedBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAssignedBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAssignedProductNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherBudgetTransactionNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherDeactivatedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpiredNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpireSoonBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpireSoonProductNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherPhysicalCardRequestedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherSharedByEmailNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersApprovedBudgetNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersApprovedProductsNotification;
use App\Notifications\Organizations\FundProviders\FundProviderSponsorChatMessageNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersRevokedBudgetNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersRevokedProductsNotification;
use App\Notifications\Organizations\FundProviders\FundProviderTransactionBunqSuccessNotification;
use App\Notifications\Organizations\FundRequests\FundRequestCreatedValidatorNotification;
use App\Notifications\Organizations\Funds\BalanceLowNotification;
use App\Notifications\Organizations\Funds\BalanceSuppliedNotification;
use App\Notifications\Organizations\Funds\FundCreatedNotification;
use App\Notifications\Organizations\Funds\FundEndedNotification;
use App\Notifications\Organizations\Funds\FundExpiringNotification;
use App\Notifications\Organizations\Funds\FundProductAddedNotification;
use App\Notifications\Organizations\Funds\FundProviderAppliedNotification;
use App\Notifications\Organizations\Funds\FundProviderChatMessageNotification;
use App\Notifications\Organizations\Funds\FundStartedNotification;
use App\Notifications\Organizations\Products\ProductApprovedNotification;
use App\Notifications\Organizations\Products\ProductExpiredNotification;
use App\Notifications\Organizations\Products\ProductReservedNotification;
use App\Notifications\Organizations\Products\ProductRevokedNotification;
use App\Notifications\Organizations\Products\ProductSoldOutNotification;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Notification\Models\NotificationPreference;
use App\Services\Forus\Notification\Models\NotificationUnsubscription;
use App\Services\Forus\Notification\Models\NotificationUnsubscriptionToken;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepo implements INotificationRepo
{
    protected static array $notifications = [
        // employee notifications
        IdentityAddedEmployeeNotification::class,
        IdentityChangedEmployeeRolesNotification::class,
        IdentityRemovedEmployeeNotification::class,
        IdentityAssignedToFundRequestBySupervisorNotification::class,

        // fund providers
        FundProvidersApprovedBudgetNotification::class,
        FundProvidersApprovedProductsNotification::class,
        FundProvidersRevokedBudgetNotification::class,
        FundProvidersRevokedProductsNotification::class,
        FundProviderSponsorChatMessageNotification::class,

        IdentityRequesterProviderApprovedBudgetNotification::class,
        IdentityRequesterProviderApprovedProductsNotification::class,

        FundRequestCreatedValidatorNotification::class,
        IdentityFundRequestCreatedNotification::class,
        IdentityFundRequestDeniedNotification::class,
        IdentityFundRequestApprovedNotification::class,
        IdentityFundRequestDisregardedNotification::class,
        IdentityFundRequestRecordFeedbackRequestedNotification::class,

        // funds
        FundCreatedNotification::class,
        FundStartedNotification::class,
        FundEndedNotification::class,
        FundExpiringNotification::class,

        FundProductAddedNotification::class,
        FundProviderAppliedNotification::class,
        FundProviderChatMessageNotification::class,

        BalanceLowNotification::class,
        BalanceSuppliedNotification::class,

        // product reservations
        IdentityProductReservationCreatedNotification::class,
        IdentityProductReservationAcceptedNotification::class,
        IdentityProductReservationCanceledNotification::class,
        IdentityProductReservationRejectedNotification::class,

        // products
        ProductApprovedNotification::class,
        ProductExpiredNotification::class,
        ProductReservedNotification::class,
        ProductRevokedNotification::class,
        ProductSoldOutNotification::class,

        // vouchers
        IdentityProductVoucherSharedNotification::class,
        IdentityVoucherAssignedBudgetNotification::class,
        IdentityVoucherAssignedProductNotification::class,

        IdentityProductVoucherAddedNotification::class,
        IdentityProductVoucherReservedNotification::class,
        IdentityVoucherAddedBudgetNotification::class,

        IdentityVoucherDeactivatedNotification::class,
        IdentityVoucherExpiredNotification::class,
        IdentityProductVoucherExpiredNotification::class,

        IdentityVoucherExpireSoonBudgetNotification::class,
        IdentityVoucherExpireSoonProductNotification::class,

        IdentityVoucherPhysicalCardRequestedNotification::class,
        IdentityVoucherSharedByEmailNotification::class,

        // voucher transactions
        IdentityVoucherBudgetTransactionNotification::class,
        IdentityProductVoucherTransactionNotification::class,
        FundProviderTransactionBunqSuccessNotification::class,

    ];

    /**
     * Map between type keys and Mail classes.
     * @var array
     */
    protected static array $mailMap = [
        // User generated emails
        'vouchers.send_voucher' => SendVoucherMail::class,
        'vouchers.share_voucher' => ShareProductVoucherMail::class,
        'vouchers.payment_success' => PaymentSuccessBudgetMail::class,

        // Mails for sponsors/providers
        'funds.provider_applied' => ProviderAppliedMail::class,
        'funds.provider_approved' => ProviderApprovedMail::class,
        'funds.product_sold_out' => ProductSoldOutMail::class,
        'funds.fund_expires' => VoucherExpireSoonBudgetMail::class,
        'funds.balance_warning' => FundBalanceWarningMail::class,
        'funds.product_reserved' => ProductBoughtProviderMail::class,
        'funds_requests.assigned_by_supervisor' => FundRequestAssignedBySupervisorMail::class,

        // Authorization emails
        'auth.user_login' => UserLoginMail::class,
        'auth.email_activation' => EmailActivationMail::class,

        // Digests
        'digest.daily_sponsor' => DigestSponsorMail::class,
        'digest.daily_validator' => DigestValidatorMail::class,
        'digest.daily_requester' => DigestRequesterMail::class,
        'digest.daily_provider_funds' => DigestProviderFundsMail::class,
        'digest.daily_provider_products' => DigestProviderProductsMail::class,
        'digest.daily_sponsor_product_updates' => DigestSponsorProductUpdatesMail::class,
    ];

    /**
     * List all push notification keys.
     * @var array
     */
    protected static array $pushNotificationKeys = [
        'voucher.assigned',
        'voucher.transaction',
        'employee.created',
        'employee.deleted',
        'bunq.transaction_success',
        'funds.provider_approved',
    ];

    /**
     * Emails that you can't unsubscribe from.
     * @var array
     */
    protected static array $mandatoryEmail = [
        'auth.user_login', 'auth.email_activation', 'vouchers.share_voucher',
        'vouchers.send_voucher',
    ];

    /**
     * Push notifications that you can't unsubscribe from (currently none).
     * @var array
     */
    protected static array $mandatoryPushNotifications = [];

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
    public static function getPushNotificationKeys(): array
    {
        return self::$pushNotificationKeys;
    }

    /**
     * @return array
     */
    public static function getMandatoryMailKeys(): array
    {
        return self::$mandatoryEmail;
    }

    /**
     * @param bool $visibleOnly
     * @return Builder
     */
    public function getSystemNotificationsQuery(bool $visibleOnly = false): Builder
    {
        return SystemNotification::where(function (Builder $builder) use ($visibleOnly) {
            if ($visibleOnly) {
                $builder->where('visible', true);
            }
        })->orderBy('group')->orderBy('order');
    }

    /**
     * @param bool $visibleOnly
     * @return Collection|SystemNotification[]
     */
    public function getSystemNotifications(bool $visibleOnly = false): Collection|array
    {
        return $this->getSystemNotificationsQuery($visibleOnly)->get();
    }

    /**
     * Is email unsubscribed from all emails.
     * @param string $email
     * @return bool
     */
    public function isEmailUnsubscribed(string $email): bool
    {
        return NotificationUnsubscription::where(compact('email'))->exists();
    }

    /**
     * Check if Mail class can be unsubscribed.
     * @param string $emailClass
     * @return bool
     */
    public function isMailUnsubscribable(string $emailClass): bool
    {
        $keys = array_flip(self::getMailMap());

        if (!isset($keys[$emailClass])) {
            return false;
        }

        return !in_array($keys[$emailClass], self::getMandatoryMailKeys(), true);
    }

    /**
     * Check if Push notification can be unsubscribed.
     * @param string $pushKey
     * @return bool
     */
    public function isPushNotificationUnsubscribable(string $pushKey): bool
    {
        $isValidKey = in_array($pushKey, self::getPushNotificationKeys(), true);
        $isMandatoryKey = in_array($pushKey, self::$mandatoryPushNotifications, true);

        return $isValidKey && !$isMandatoryKey;
    }

    /**
     * Check if Push notification can be unsubscribed.
     * @param string $identity_address
     * @param string $pushKey
     * @return bool
     */
    public function isPushNotificationUnsubscribed(string $identity_address, string $pushKey): bool
    {
        return NotificationPreference::where([
            'identity_address' => $identity_address,
            'subscribed' => false,
            'type' => 'push',
            'key' => $pushKey,
        ])->exists();
    }

    /**
     * Is email $emailClass unsubscribed.
     * @param string $identity_address
     * @param string $emailClass
     * @throws Exception
     * @return bool
     */
    public function isEmailTypeUnsubscribed($identity_address, $emailClass): bool
    {
        if (!$this->isMailUnsubscribable($emailClass)) {
            return false;
        }

        $key = array_flip(self::getMailMap())[$emailClass];
        $type = 'email';
        $subscribed = false;
        $filters = compact('identity_address', 'key', 'subscribed', 'type');

        return NotificationPreference::where($filters)->exists();
    }

    /**
     * Create new unsubscription from all emails link.
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeUnsubLink(string $email, string $token = null): string
    {
        return url('/notifications/unsubscribe/' . $this->makeToken($email, $token));
    }

    /**
     * Create new unsubscription from all emails link.
     * @param string $email
     * @param string|null $token
     * @return string
     */
    public function makeReSubLink(string $email, string $token = null): string
    {
        return url('/notifications/subscribe/' . $this->makeToken($email, $token));
    }

    /**
     * Unsubscribe email from all notifications.
     * @param string $email
     */
    public function unsubscribeEmail(string $email): void
    {
        NotificationUnsubscription::firstOrCreate(compact('email'));
    }

    /**
     * Remove email unsubscription from all notifications.
     * @param string $email
     * @throws Exception
     */
    public function reSubscribeEmail(string $email): void
    {
        NotificationUnsubscription::where(compact('email'))->delete();
    }

    /**
     * @param string $token
     * @param bool $active
     * @return string|null
     */
    public function emailByUnsubscribeToken(string $token, bool $active = true): ?string
    {
        return NotificationUnsubscriptionToken::findByToken($token, $active)->email ?? null;
    }

    /**
     * @param string $identityAddress
     * @return array
     */
    public function getNotificationPreferences(string $identityAddress): array
    {
        $unsubscribedKeys = NotificationPreference::where([
            'identity_address' => $identityAddress,
            'subscribed' => false,
        ])->get();

        $unsubscribedMailKeys = $unsubscribedKeys->where('type', 'email')->keyBy('key');
        $unsubscribedPushKeys = $unsubscribedKeys->where('type', 'push')->keyBy('key');

        $mailKeys = array_map(fn ($mailKey) => [
            'key' => $mailKey,
            'type' => 'email',
            'subscribed' => !($unsubscribedMailKeys[$mailKey] ?? false),
        ], $this->mailTypeKeys());

        $pushKeys = array_map(fn ($pushKey) => [
            'key' => $pushKey,
            'type' => 'push',
            'subscribed' => !($unsubscribedPushKeys[$pushKey] ?? false),
        ], self::getPushNotificationKeys());

        return [...$mailKeys, ...$pushKeys];
    }

    /**
     * @param string $identityAddress
     * @param array $data
     * @return array
     */
    public function updateIdentityPreferences(string $identityAddress, array $data): array
    {
        $data_keys = array_keys(array_pluck($data, 'subscribed', 'key'));
        $preference_keys = $this->allPreferenceKeys();

        foreach ($data as $setting) {
            if (array_intersect($preference_keys, $data_keys)) {
                NotificationPreference::firstOrCreate([
                    'identity_address' => $identityAddress,
                    'key' => $setting['key'],
                    'type' => $setting['type'],
                ])->update([
                    'subscribed' => $setting['subscribed'],
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
        return array_values(array_diff(array_keys(self::getMailMap()), self::getMandatoryMailKeys()));
    }

    /**
     * @return array
     */
    public function pushNotificationTypeKeys(): array
    {
        return self::getPushNotificationKeys();
    }

    /**
     * @return array
     */
    public function allPreferenceKeys(): array
    {
        return array_merge($this->mailTypeKeys(), $this->pushNotificationTypeKeys());
    }

    /**
     * Try to reuse existing token or create new one.
     * @param string $email
     * @param string|null $token
     * @return string
     */
    private function makeToken(string $email, string $token = null): string
    {
        $model = $token ? NotificationUnsubscriptionToken::findByToken($token) : null;

        return ($model ?: NotificationUnsubscriptionToken::makeToken($email))->token;
    }
}
