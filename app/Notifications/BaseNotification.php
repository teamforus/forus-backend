<?php

namespace App\Notifications;

use App\Models\Implementation;
use App\Models\SystemNotification;
use App\Services\EventLogService\Models\EventLog;
use App\Models\Identity;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Class BaseNotification
 * @package App\Notifications
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const SCOPE_WEBSHOP = 'webshop';
    public const SCOPE_SPONSOR = 'sponsor';
    public const SCOPE_PROVIDER = 'provider';
    public const SCOPE_VALIDATOR = 'validator';

    protected static ?string $key;
    protected static ?string $scope;
    protected static ?string $pushKey;

    protected ?EventLog $eventLog;
    protected array $meta = [];
    protected ?Implementation $implementation;

    public const VARIABLES = [
        "notifications_identities.added_employee" => [
            "dashboard_auth_button", "employee_roles", "organization_name",
            "download_me_app_link", "download_me_app_button",
        ],
        "notifications_identities.changed_employee_roles" => [
            "employee_roles", "organization_name",
        ],
        "notifications_identities.removed_employee" => [
            "organization_name",
        ],
        "notifications_fund_providers.approved_budget" => [
            "fund_name", "provider_dashboard_link", 'provider_dashboard_button',
            "provider_name", "sponsor_name",
        ],
        "notifications_fund_providers.approved_products" => [
            "fund_name"
        ],
        "notifications_fund_providers.revoked_budget" => [
            "fund_name", "provider_name", "sponsor_name", "sponsor_phone",
        ],
        "notifications_fund_providers.revoked_products" => [
            "fund_name",
        ],
        "notifications_fund_providers.sponsor_message" => [
            "product_name", "sponsor_name",
        ],
        "notifications_identities.requester_provider_approved_budget" => [
            "fund_name", "provider_name", "sponsor_name",
        ],
        "notifications_identities.requester_provider_approved_products" => [
            "fund_name", "provider_name", "sponsor_name",
        ],
        "notifications_fund_requests.created_validator_employee" => [
            "fund_name",
        ],
        "notifications_identities.fund_request_created" => [
            "fund_name", "sponsor_name", "webshop_button",
        ],
        "notifications_identities.fund_request_denied" => [
            "fund_name", "fund_request_note", "fund_request_state", "sponsor_email",
            "sponsor_name", "sponsor_phone",
        ],
        "notifications_identities.fund_request_approved" => [
            "app_link", "fund_name", "sponsor_name", "webshop_button", "webshop_link"
        ],
        "notifications_identities.fund_request_disregarded" => [
            "fund_name", "sponsor_email", "sponsor_name", "sponsor_phone",
        ],
        "notifications_identities.fund_request_record_declined" => [
            "fund_name", "rejection_note", "webshop_link", "webshop_button",
        ],
        "notifications_identities.fund_request_feedback_requested" => [
            "fund_name", "fund_request_clarification_question", "sponsor_name",
            "webshop_clarification_link", "webshop_clarification_button",
        ],
        "notifications_funds.created" => [
            "fund_name",
        ],
        "notifications_funds.started" => [
            "fund_name",
        ],
        "notifications_funds.ended" => [
            "fund_end_date_locale", "fund_name", "fund_start_date_locale",
        ],
        "notifications_funds.expiring" => [
            "fund_end_date_locale", "fund_name",
        ],
        "notifications_funds.product_added" => [
            "fund_name", "provider_name",
        ],
        "notifications_funds.provider_applied" => [
            "fund_name", "provider_name", "sponsor_dashboard_button", "sponsor_dashboard_link",
            "sponsor_name",
        ],
        "notifications_funds.provider_message" => [
            "fund_name", "product_name", "provider_name",
        ],
        "notifications_funds.product_subsidy_removed" => [
            "product_name", "provider_name",
        ],
        "notifications_funds.balance_low" => [
            "fund_budget_left_locale", "fund_name", "fund_notification_amount_locale",
            "sponsor_name", "fund_transaction_costs","fund_transaction_costs_locale"
        ],
        "notifications_funds.balance_supplied" => [
            "fund_name", "fund_top_up_amount_locale",
        ],
        "notifications_identities.product_reservation_created" => [
            "product_name",
        ],
        "notifications_identities.product_reservation_accepted" => [
            "product_name", "provider_name",
        ],
        "notifications_identities.product_reservation_canceled" => [
            "product_name",
        ],
        "notifications_identities.product_reservation_rejected" => [
            "product_name", "provider_name",
        ],
        "notifications_products.approved" => [
            "fund_name", "product_name", "sponsor_name",
        ],
        "notifications_products.expired" => [
            "product_name",
        ],
        "notifications_products.reserved" => [
            "expiration_date", "product_name",
        ],
        "notifications_products.revoked" => [
            "fund_name", "product_name", "sponsor_name",
        ],
        "notifications_products.sold_out" => [
            "product_name", "provider_dashboard_button", "provider_dashboard_link",
        ],
        "notifications_identities.product_voucher_shared" => [
            "product_name", "provider_name", "qr_token", "requester_email",
            "voucher_share_message",
        ],
        "notifications_identities.identity_voucher_assigned_budget" => [
            "fund_name", "qr_token", "voucher_amount_locale", "voucher_expire_date_locale",
            "webshop_link", "webshop_button",
        ],
        "notifications_identities.identity_voucher_assigned_subsidy" => [
            "fund_name", "webshop_link", "webshop_button", "sponsor_email", "sponsor_phone",
            "voucher_expire_date_locale", "qr_token",
        ],
        "notifications_identities.identity_voucher_assigned_product" => [
            "implementation_name", "product_name", "provider_email", "provider_name",
            "provider_phone", "qr_token", "sponsor_name", "voucher_expire_date_locale",
        ],
        "notifications_identities.product_voucher_added" => [
            "product_name", "provider_name", "voucher_amount_locale",
            "voucher_expire_date_locale",
        ],
        "notifications_identities.product_voucher_reserved" => [
            "product_name", "product_price_locale", "provider_email", "provider_name",
            "provider_phone", "qr_token", "voucher_expire_date_locale",
        ],
        "notifications_identities.voucher_added_subsidy" => [
            "fund_name", "voucher_expire_date_locale",
        ],
        "notifications_identities.voucher_added_budget" => [
            "fund_name", "voucher_amount_locale", "voucher_expire_date_locale",
        ],
        "notifications_identities.voucher_deactivated" => [
            "deactivation_date_locale", "fund_name",
            "sponsor_email", "sponsor_name", "sponsor_phone",
        ],
        "notifications_identities.budget_voucher_expired" => [
            "fund_name"
        ],
        "notifications_identities.product_voucher_expired" => [
            "fund_name"
        ],
        "notifications_identities.voucher_expire_soon_budget" => [
            "fund_end_date_locale", "fund_end_date_minus1_locale", "fund_name", "fund_start_year",
            "webshop_link", "webshop_button", "sponsor_email", "sponsor_name", "sponsor_phone",
        ],
        "notifications_identities.voucher_expire_soon_product" => [
            "fund_name"
        ],
        "notifications_identities.voucher_physical_card_requested" => [
            "address", "city", "fund_name", "house", "house_addition",
            "postcode", "sponsor_email", "sponsor_phone",
        ],
        "notifications_identities.voucher_shared_by_email" => [],
        "notifications_identities.voucher_budget_transaction" => [
            "amount", "fund_name", "voucher_amount_locale",
        ],
        "notifications_identities.voucher_subsidy_transaction" => [
            "fund_name", "product_name", "subsidy_new_limit", "webshop_link", "webshop_button",
        ],
        "notifications_identities.product_voucher_transaction" => [
            "product_name"
        ],
        "notifications_fund_providers.bunq_transaction_success" => [
            "voucher_transaction_amount_locale"
        ],
        'notifications_identities.requester_sponsor_custom_notification' => [
            "fund_name", "sponsor_name", "webshop_link", "webshop_button",
        ],
    ];

    /**
     * Create a new notification instance.
     *
     * BaseNotification constructor.
     * @param EventLog|null $eventLog
     * @param array $meta
     * @param Implementation|null $implementation
     */
    public function __construct(
        ?EventLog $eventLog,
        array $meta = [],
        ?Implementation $implementation = null
    ) {
        $this->eventLog = $eventLog;
        $this->meta = array_merge($this->meta, $meta);
        $this->queue = config('forus.notifications.notifications_queue_name');
        $this->implementation = $implementation;
    }

    /**
     * @param string|null $key
     * @return array
     */
    public static function getVariables(?string $key = null): array
    {
        return array_merge(static::VARIABLES[$key ?: static::$key] ?? [], [
            'email_logo', 'email_signature',
        ]);
    }

    /**
     * @return string|null
     */
    public static function getKey(): ?string
    {
        return static::$key;
    }

    /**
     * @return string|null
     */
    public static function getScope(): ?string
    {
        return static::$scope;
    }

    /**
     * @return string|null
     */
    public static function getPushKey(): ?string
    {
        return static::$pushKey;
    }

    /**
     * @return array
     */
    public static function getChannels(): array
    {
        $systemNotification = SystemNotification::getByKey(static::$key);

        return $systemNotification ? $systemNotification->channels() : [];
    }

    /**
     * @return NotificationService
     */
    public function getNotificationService(): NotificationService
    {
        return resolve('forus.services.notification');
    }

    /**
     * @param string $email
     * @param Mailable $mailable
     * @return bool
     */
    public function sendMailNotification(string $email, Mailable $mailable): bool
    {
        return $this->getNotificationService()->sendMailNotification($email, $mailable);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     * @noinspection PhpUnused
     */
    public function via(): array
    {
        $channelKeys = static::getChannels();
        $channels = ['database'];

        if (in_array('mail', $channelKeys)) {
            $channels[] = MailChannel::class;
        }

        if (in_array('push', $channelKeys)) {
            $channels[] = PushChannel::class;
        }

        return $this->eventLog ? $channels : [];
    }

    /**
     * Serialize and save the notification in the database
     *
     * @return string[]
     * @noinspection PhpUnused
     */
    public function toDatabase(): array
    {
        return array_merge([
            'key' => static::$key,
            'scope' => static::$scope,
            'event_id' => $this->eventLog->id,
        ], $this->meta);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toMail(Identity $identity): void {}

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     * @return void
     */
    public function toPush(Identity $identity): void
    {
        $template = SystemNotification::findTemplate(
            static::getKey(),
            'push',
            $this->implementation->key ?? Implementation::KEY_GENERAL
        );

        $this->getNotificationService()->sendPushNotification(
            $identity->address,
            str_var_replace($template->title, $this->eventLog->data),
            str_var_replace($template->content, $this->eventLog->data),
            static::getPushKey()
        );
    }

    /**
     * Generate and send notifications for EventLog instance
     *
     * @param EventLog $event
     * @return bool
     */
    public static function send(EventLog $event): bool
    {
        try {
            $implementation = Implementation::byKey($event->data['implementation_key']);
            $identities = static::eligibleIdentities($event->loggable, $event);
            $notification = new static($event, static::getMeta($event->loggable), $implementation);

            \Illuminate\Support\Facades\Notification::send($identities, $notification);
        } catch (\Throwable $e) {
            if ($logger = logger()) {
                $logger->error(sprintf(
                    "Unable to create notification:\n %s \n %s \n %s",
                    $e->getMessage(),
                    $e->getFile() . ": ". $e->getLine(),
                    $e->getTraceAsString()
                ));
            }

            return false;
        }

        return true;
    }

    /**
     * Get additional data to store in notification
     *
     * @param Model $loggable
     * @return array
     * @throws \Exception
     */
    abstract public static function getMeta(mixed $loggable): array;

    /**
     * Get identities which are eligible for the notification
     *
     * @param Model $loggable
     * @param EventLog $eventLog
     * @return Collection
     * @throws \Exception
     */
    abstract public static function eligibleIdentities(mixed $loggable, EventLog $eventLog): Collection;
}
