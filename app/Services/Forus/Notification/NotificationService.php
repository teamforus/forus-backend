<?php

namespace App\Services\Forus\Notification;

use App\Mail\Auth\UserLoginMail;
use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestProviderMail;
use App\Mail\Funds\FundRequestRecords\FundRequestRecordDeclinedMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\FundClosed;
use App\Mail\Funds\FundClosedProvider;
use App\Mail\Funds\FundExpiredMail;
use App\Mail\Funds\FundStartedMail;
use App\Mail\Funds\ProviderInvitedMail;
use App\Mail\User\EmailActivationMail;
use App\Mail\User\IdentityEmailVerificationMail;
use App\Mail\Vouchers\AssignedVoucherMail;
use App\Mail\Forus\FundStatisticsMail;
use App\Mail\Forus\ForusFundCreatedMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\RequestPhysicalCardMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Models\Implementation;
use App\Services\ApiRequestService\ApiRequest;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Notification\Models\NotificationToken;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Notification;

/**
 * Class MailService
 * @package App\Services\Forus\MailNotification
 */
class NotificationService
{
    public const TYPE_PUSH_IOS = NotificationToken::TYPE_PUSH_IOS;
    public const TYPE_PUSH_ANDROID = NotificationToken::TYPE_PUSH_ANDROID;

    public const TYPES = [
        self::TYPE_PUSH_IOS,
        self::TYPE_PUSH_ANDROID,
    ];

    protected $notificationRepo;
    protected $recordRepo;
    protected $apiRequest;
    protected $mailer;

    /**
     * NotificationService constructor.
     *
     * @param Mailer $mailer
     * @param ApiRequest $apiRequest
     * @param IRecordRepo $recordRepo
     * @param INotificationRepo $notificationRepo
     */
    public function __construct(
        Mailer $mailer,
        ApiRequest $apiRequest,
        IRecordRepo $recordRepo,
        INotificationRepo $notificationRepo
    ) {
        $this->mailer = $mailer;
        $this->apiRequest = $apiRequest;
        $this->recordRepo = $recordRepo;
        $this->notificationRepo = $notificationRepo;
    }

    /**
     * Add notification token for identity
     *
     * @param $identity_address
     * @param string $type
     * @param string $token
     * @return bool
     */
    public function storeNotificationToken(
        $identity_address,
        string $type,
        string $token
    ): bool {
        if (!in_array($type, self::TYPES, true)) {
            return false;
        }

        NotificationToken::firstOrCreate(
            compact('identity_address', 'type', 'token')
        );

        return true;
    }

    /**
     * Remove notification token
     *
     * @param string $token
     * @param string|null $type
     * @param null $identity_address
     */
    public function removeNotificationToken(
        string $token,
        string $type = null,
        $identity_address = null
    ): void {
        $query = NotificationToken::where(compact('token'));

        if ($type) {
            $query->where(compact('type'));
        }

        if ($identity_address) {
            $query->where(compact('identity_address'));
        }

        $query->delete();
    }

    /**
     * Send push notification
     *
     * @param $identity_address
     * @param string $title
     * @param string $body
     * @param ?string $key
     * @return bool
     */
    public function sendPushNotification(
        $identity_address,
        string $title,
        string $body,
        string $key = null
    ): bool {
        if ($this->isPushUnsubscribable($key) &&
            $this->isPushUnsubscribed($identity_address, $key)) {
            return false;
        }

        /** @var NotificationToken[] $notificationTokens */
        $notificationTokens = NotificationToken::where([
            'identity_address' => $identity_address
        ])->get();

        foreach ($notificationTokens as $notificationToken) {
            if (!config(sprintf(
                'broadcasting.connections.%s',
                $notificationToken->type
            ))) {
                continue;
            }

            $notification = $notificationToken->makeBasicNotification(
                $title, $body
            );

            if ($notification) {
                Notification::route(
                    $notificationToken->type,
                    $notificationToken->token
                )->notify($notification);
            }
        }

        return true;
    }

    /**
     * @param string $email
     * @param Mailable $param
     * @return bool
     */
    public function sendMailNotification(
        string $email,
        Mailable $param
    ): bool {
        return $this->sendMail($email, $param);
    }

    /**
     * todo: has to be migrated
     * Invite provider to new fund
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $provider_name
     * @param string $sponsor_name
     * @param string|null $sponsor_phone
     * @param string|null $sponsor_email
     * @param string $fund_name
     * @param string $fund_start_date
     * @param string $fund_end_date
     * @param string $from_fund_name
     * @param string $invitation_link
     * @return bool
     */
    public function providerInvited(
        string $email,
        ?EmailFrom $emailFrom,
        string $provider_name,
        string $sponsor_name,
        ?string $sponsor_phone,
        ?string $sponsor_email,
        string $fund_name,
        string $fund_start_date,
        string $fund_end_date,
        string $from_fund_name,
        string $invitation_link
    ): bool {
        return $this->sendMail($email, new ProviderInvitedMail(
            $provider_name,
            $sponsor_name,
            $sponsor_phone,
            $sponsor_email,
            $fund_name,
            $fund_start_date,
            $fund_end_date,
            $from_fund_name,
            $invitation_link,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Notify user that fund request record declined
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string|null $rejectionNote
     * @param string $fundName
     * @param string $webshopLink
     * @return bool
     */
    public function fundRequestRecordDeclined(
        string $email,
        ?EmailFrom $emailFrom,
        ?string $rejectionNote,
        string $fundName,
        string $webshopLink
    ): bool {
        return $this->sendMail($email, new FundRequestRecordDeclinedMail(
            $fundName,
            $rejectionNote,
            $webshopLink,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Notify providers that new fund was started
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $fund_name
     * @param string $sponsor_name
     * @return bool
     */
    public function newFundStarted(
        string $email,
        ?EmailFrom $emailFrom,
        string $fund_name,
        string $sponsor_name
    ): bool {
        return $this->sendMail($email, new FundStartedMail(
            $fund_name,
            $sponsor_name,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Notify company that new fund was created
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $fund_name
     * @param string $organization_name
     * @return bool
     */
    public function newFundCreatedNotifyForus(
        string $email,
        ?EmailFrom $emailFrom,
        string $fund_name,
        string $organization_name
    ): bool {
        return $this->sendMail($email, new ForusFundCreatedMail(
            $fund_name,
            $organization_name,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Notify users that fund was closed
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $fund_name
     * @param string $fund_contact
     * @param string $sponsor_name
     * @param string $webshop_link
     * @return bool
     */
    public function fundClosed(
        string $email,
        ?EmailFrom $emailFrom,
        string $fund_name,
        string $fund_contact,
        string $sponsor_name,
        string $webshop_link
    ): bool {
        return $this->sendMail($email, new FundClosed(
            $fund_name,
            $fund_contact,
            $sponsor_name,
            $webshop_link,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Notify providers that fund was closed
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $fund_name
     * @param string $fund_start_date
     * @param string $fund_end_date
     * @param string $sponsor_name
     * @param string $dashboard_link
     * @return bool|null
     */
    public function fundClosedProvider(
        string $email,
        ?EmailFrom $emailFrom,
        string $fund_name,
        string $fund_start_date,
        string $fund_end_date,
        string $sponsor_name,
        string $dashboard_link
    ): ?bool {
        return $this->sendMail($email, new FundClosedProvider(
            $fund_name,
            $fund_start_date,
            $fund_end_date,
            $sponsor_name,
            $dashboard_link,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Send number of fund users
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $fund_name
     * @param string $sponsor_name
     * @param int $sponsor_amount
     * @param int $provider_amount
     * @param int $requester_amount
     * @return bool
     */
    public function sendFundUserStatisticsReport(
        string $email,
        ?EmailFrom $emailFrom,
        string $fund_name,
        string $sponsor_name,
        int $sponsor_amount,
        int $provider_amount,
        int $requester_amount
    ): bool {
        return $this->sendMail($email, new FundStatisticsMail(
            $fund_name,
            $sponsor_name,
            $sponsor_amount,
            $provider_amount,
            $requester_amount,
            $sponsor_amount + $provider_amount + $requester_amount,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Send voucher by email
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $fund_name
     * @param int $voucher_amount
     * @param string $voucher_last_active_day
     * @param string $fund_product_name
     * @param string $qr_token
     * @return bool
     */
    public function sendVoucher(
        string $email,
        ?EmailFrom $emailFrom,
        string $fund_name,
        int $voucher_amount,
        string $voucher_last_active_day,
        string $fund_product_name,
        string $qr_token
    ): bool {
        return $this->sendMail($email, new SendVoucherMail(
            $fund_name,
            $fund_product_name,
            $qr_token,
            $voucher_amount,
            $voucher_last_active_day,
            $emailFrom
        ));
    }

    /**
     * Send assigned voucher to email
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $type
     * @param $data
     * @return bool
     */
    public function assignVoucher(
        string $email,
        ?EmailFrom $emailFrom,
        string $type,
        $data
    ): bool {
        $mailable = new AssignedVoucherMail($emailFrom, $type, $data);
        return $mailable ? $this->sendMail($email, $mailable) : false;
    }

    /**
     * Request a physical card
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param $data
     * @return bool
     */
    public function requestPhysicalCard(
        string $email,
        ?EmailFrom $emailFrom,
        $data
    ): bool {
        return $this->sendMail($email, new RequestPhysicalCardMail(
            $emailFrom,
            $data
        ));
    }

    /**
     * Send restore identity link to address email
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $link
     * @param string $source
     * @return bool
     */
    public function loginViaEmail(
        string $email,
        ?EmailFrom $emailFrom,
        string $link,
        string $source
    ): bool {
        return $this->sendMail($email, new UserLoginMail(
            $link,
            $source,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Notify provider that a product was sold out.
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $product_name
     * @param string $sponsor_dashboard_url
     * @return bool
     */
    public function productSoldOut(
        string $email,
        ?EmailFrom $emailFrom,
        string $product_name,
        string $sponsor_dashboard_url
    ): bool {
        return $this->sendMail($email, new ProductSoldOutMail(
            $product_name,
            $sponsor_dashboard_url,
            $emailFrom
        ));
    }

    /**
     * Send email confirmation link
     *
     * @param string $email
     * @param string $clientType
     * @param EmailFrom|null $emailFrom
     * @param string $confirmationLink
     * @return bool
     */
    public function sendEmailConfirmationLink(
        string $email,
        string $clientType,
        ?EmailFrom $emailFrom,
        string $confirmationLink
    ): bool {
        return $this->sendMail($email, new EmailActivationMail(
            $clientType,
            $confirmationLink,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Notify user that voucher is about to expire
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $fund_name
     * @param string $sponsor_name
     * @param $start_date
     * @param $end_date
     * @param string $sponsor_phone
     * @param string $sponsor_email
     * @param string $webshopLink
     * @return bool
     */
    public function voucherExpireSoon(
        string $email,
        ?EmailFrom $emailFrom,
        string $fund_name,
        string $sponsor_name,
        $start_date,
        $end_date,
        string $sponsor_phone,
        string $sponsor_email,
        string $webshopLink
    ): bool {

        return $this->sendMail($email, new FundExpiredMail(
            $fund_name,
            $sponsor_name,
            $start_date,
            $end_date,
            $sponsor_phone,
            $sponsor_email,
            $webshopLink,
            $emailFrom
        ));
    }

    /**
     * todo: has to be migrated
     * Fund balance reached the threshold set in preferences
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $link
     * @param string $sponsor_name
     * @param string $fund_name
     * @param string $notification_amount
     * @param string $budget_left
     * @return bool
     */
    public function fundBalanceWarning(
        string $email,
        ?EmailFrom $emailFrom,
        string $link,
        string $sponsor_name,
        string $fund_name,
        string $notification_amount,
        string $budget_left
    ): bool {
        return $this->sendMail($email, new FundBalanceWarningMail(
            $fund_name,
            $sponsor_name,
            $notification_amount,
            $budget_left,
            $link,
            $emailFrom
        ));
    }

    /**
     * Send verification link for identity email
     *
     * @param string $email
     * @param ?EmailFrom $emailFrom
     * @param string $link
     * @return bool|null
     */
    public function sendEmailVerificationLink(
        string $email,
        ?EmailFrom $emailFrom,
        string $link
    ): bool {
        return $this->sendMail($email, new IdentityEmailVerificationMail($link, $emailFrom));
    }

    /**
     * Send digest
     *
     * @param string $email
     * @param BaseDigestMail $mailable
     * @return bool|null
     */
    public function sendDigest(string $email, BaseDigestMail $mailable): ?bool {
        return $this->sendMail($email, $mailable);
    }

    /**
     * Send the mail and check for failure
     *
     * @param $email
     * @param Mailable $mailable
     * @return bool
     */
    private function sendMail($email, Mailable $mailable): bool {
        if (config()->get('mail.disable', false)) {
            return true;
        }

        try {
            if ($this->isUnsubscribed($email, $mailable)) {
                return false;
            }

            $unsubscribeLink = $this->notificationRepo->makeUnsubLink($email);
            $notificationPreferencesLink = sprintf(
                '%s/%s',
                rtrim(Implementation::active()['url_sponsor'], '/'),
                'preferences/notifications');

            /** @var Queueable|Mailable $message */
            $message = $mailable->with(compact(
                'email', 'unsubscribeLink', 'notificationPreferencesLink'
            ));

            $message = $message->onQueue(config(
                'forus.notifications.email_queue_name'
            ));

            $this->mailer->to($email)->queue($message);

            return $this->checkFailure(get_class($mailable));
        } catch (\Exception $exception) {
            $this->logFailure($exception);
            return false;
        }
    }

    /**
     * Check if email is unsubscribed from all message or current email type
     * @param string $email
     * @param Mailable $mailable
     * @return bool
     * @throws \Exception
     */
    protected function isUnsubscribed(string $email, Mailable $mailable): bool {
        $mailClass = get_class($mailable);

        return $this->notificationRepo->isMailUnsubscribable($mailClass) && (
            $this->notificationRepo->isEmailUnsubscribed($email) ||
            $this->notificationRepo->isEmailTypeUnsubscribed(
                $this->recordRepo->identityAddressByEmail($email),
                $mailClass
            )
        );
    }

    /**
     * Check if Push notification can be subscribed
     *
     * @param string $key
     * @return bool
     */
    protected function isPushUnsubscribable(string $key): bool {
        return $this->notificationRepo->isPushNotificationUnsubscribable($key);
    }

    /**
     * Check if Push notification is unsubscribed
     *
     * @param string $identity_address
     * @param string $key
     * @return bool
     */
    protected function isPushUnsubscribed(string $identity_address, string $key): bool {
        return $this->notificationRepo->isPushNotificationUnsubscribed(
            $identity_address,
            $key
        );
    }

    /**
     * Check for failure and log in case of error
     *
     * @param string $mailName
     * @return bool
     */
    private function checkFailure(string $mailName): bool {
        if (!$this->mailer->failures()) {
            return true;
        }

        $this->logFailure($mailName);

        return false;
    }

    /**
     * Log failure
     *
     * @param string|null $message
     * @return void
     */
    private function logFailure(?string $message): void {
        if ($logger = logger()) {
            $logger->error(sprintf(
                'Error sending notification: `%s`',
                $message
            ));
        }
    }
}
