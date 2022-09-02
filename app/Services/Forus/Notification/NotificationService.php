<?php

namespace App\Services\Forus\Notification;

use App\Mail\Auth\UserLoginMail;
use App\Mail\Digest\BaseDigestMail;
use App\Mail\User\EmailActivationMail;
use App\Mail\User\IdentityEmailVerificationMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Notification\Models\NotificationToken;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Exception;

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

    protected INotificationRepo $notificationRepo;
    protected Mailer $mailer;

    /**
     * NotificationService constructor.
     *
     * @param Mailer $mailer
     * @param INotificationRepo $notificationRepo
     */
    public function __construct(Mailer $mailer, INotificationRepo $notificationRepo)
    {
        $this->mailer = $mailer;
        $this->notificationRepo = $notificationRepo;
    }

    /**
     * Add notification token for identity
     *
     * @param string $identity_address
     * @param string $token
     * @param string $type
     * @throws Exception
     * @return NotificationToken
     */
    public function storeNotificationToken(
        string $identity_address,
        string $token,
        string $type
    ): NotificationToken {
        if (in_array($type, self::TYPES, true)) {
            return NotificationToken::firstOrCreate(compact('identity_address', 'type', 'token'));
        }

        throw new Exception('Invalid token type');
    }

    /**
     * Remove notification token
     *
     * @param string $token
     * @param string|null $type
     * @param string|null $identity_address
     * @throws Exception
     */
    public function removeNotificationToken(
        string $token,
        string $type = null,
        string $identity_address = null
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
     * @param string $identityAddress
     * @param string $title
     * @param string $body
     * @param ?string $key
     * @return bool
     */
    public function sendPushNotification(
        string $identityAddress,
        string $title,
        string $body = '',
        string $key = null
    ): bool {
        if ($this->isPushUnsubscribable($key) && $this->isPushUnsubscribed($identityAddress, $key)) {
            return false;
        }

        foreach (NotificationToken::whereIdentityAddress($identityAddress)->get() as $token) {
            $notification = $token->makeBasicNotification($title, $body);

            if (config('broadcasting.connections.' . $token->type) && $notification) {
                Notification::route($token->type, $token->token)->notify($notification);
            }
        }

        return true;
    }

    /**
     * @param string $email
     * @param Mailable $mailable
     * @return bool
     */
    public function sendMailNotification(string $email, Mailable $mailable): bool
    {
        return $this->sendMail($email, $mailable);
    }

    /**
     * @param string $email
     * @param Mailable $mailable
     * @return bool
     */
    public function sendSystemMail(string $email, Mailable $mailable): bool
    {
        return $this->sendMail($email, $mailable);
    }

    /**
     * Send restore identity link to address email
     *
     * @param string $email
     * @param EmailFrom|null $emailFrom
     * @param string $auth_link
     * @param string $source
     * @return bool
     */
    public function loginViaEmail(
        string $email,
        ?EmailFrom $emailFrom,
        string $auth_link,
        string $source
    ): bool {
        $platform = '';
        $time = date('H:i', strtotime('1 hour'));

        if (str_contains($source, '_webshop')) {
            $platform = 'de webshop';
        } else if (str_contains($source, '_sponsor')) {
            $platform = 'het dashboard';
        } else if (str_contains($source, '_provider')) {
            $platform = 'het dashboard';
        } else if (str_contains($source, '_validator')) {
            $platform = 'het dashboard';
        } else if (str_contains($source, '_website')) {
            $platform = 'de website';
        } else if (str_contains($source, 'me_app')) {
            $platform = 'Me';
        }

        $mailable = new UserLoginMail(compact('auth_link', 'platform', 'time'), $emailFrom);

        return $this->sendMail($email, $mailable);
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
        return $this->sendMail($email, new EmailActivationMail([
            'clientType' => $clientType,
            'link' => $confirmationLink,
        ], $emailFrom));
    }

    /**
     * Send verification link for identity email
     *
     * @param string $email
     * @param ?EmailFrom $emailFrom
     * @param string $link
     * @return bool
     */
    public function sendEmailVerificationLink(
        string $email,
        ?EmailFrom $emailFrom,
        string $link
    ): bool {
        return $this->sendMail($email, new IdentityEmailVerificationMail([
            'link' => $link,
        ], $emailFrom));
    }

    /**
     * Send digest
     *
     * @param string $email
     * @param BaseDigestMail $mailable
     * @return bool|null
     */
    public function sendDigest(string $email, BaseDigestMail $mailable): ?bool
    {
        return $this->sendMail($email, $mailable);
    }

    /**
     * Send the mail and check for failure
     *
     * @param $email
     * @param Mailable $mailable
     * @return bool
     */
    private function sendMail($email, Mailable $mailable): bool
    {
        if (!Config::get('mail.disable', false)) {
            try {
                if (!$this->isUnsubscribed($email, $mailable)) {
                    $mailable = $this->addGlobalVarsToMailable($mailable, $email);
                    $this->mailer->to($email)->queue($mailable);
                }
            } catch (\Throwable $e) {
                $this->logFailure($e);
            }

            return false;
        }

        return true;
    }

    /**
     * @param Mailable $mailable
     * @param string $email
     * @return Mailable
     */
    protected function addGlobalVarsToMailable(Mailable $mailable, string $email): Mailable
    {
        $unsubscribeLink = $this->notificationRepo->makeUnsubLink($email);
        $notificationPreferencesLink = sprintf(
            '%s/%s',
            rtrim(Implementation::active()['url_sponsor'], '/'),
            'preferences/notifications');

        $mailable->with(array_merge(compact('email', 'unsubscribeLink', 'notificationPreferencesLink'), [
            'mailable' => get_class($mailable),
        ]));

        if (method_exists($mailable, 'onQueue')) {
            $mailable->onQueue(config('forus.notifications.email_queue_name'));
        }

        return $mailable;
    }

    /**
     * Check if email is unsubscribed from all message or current email type
     * @param string $email
     * @param Mailable $mailable
     * @return bool
     * @throws \Throwable
     */
    protected function isUnsubscribed(string $email, Mailable $mailable): bool
    {
        $mailClass = get_class($mailable);
        $identity = Identity::findByEmail($email);

        return $this->notificationRepo->isMailUnsubscribable($mailClass) && (
            $this->notificationRepo->isEmailUnsubscribed($email) ||
            $this->notificationRepo->isEmailTypeUnsubscribed($identity->address, $mailClass)
        );
    }

    /**
     * Check if Push notification can be subscribed
     *
     * @param string $key
     * @return bool
     */
    protected function isPushUnsubscribable(string $key): bool
    {
        return $this->notificationRepo->isPushNotificationUnsubscribable($key);
    }

    /**
     * Check if Push notification is unsubscribed
     *
     * @param string $identity_address
     * @param string $key
     * @return bool
     */
    protected function isPushUnsubscribed(string $identity_address, string $key): bool
    {
        return $this->notificationRepo->isPushNotificationUnsubscribed($identity_address, $key);
    }

    /**
     * Log failure
     *
     * @param string|null $message
     * @return void
     */
    private function logFailure(?string $message): void
    {
        if ($logger = logger()) {
            $logger->error("Error sending notification: `${$message}`");
        }
    }
}
