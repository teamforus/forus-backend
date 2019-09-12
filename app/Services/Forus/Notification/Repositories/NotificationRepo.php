<?php

namespace App\Services\Forus\Notification\Repositories;

use App\Models\NotificationPreference;
use App\Models\NotificationUnsubscription;
use App\Models\NotificationUnsubscriptionToken;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Notification\Models\NotificationType;
use Illuminate\Support\Collection;

/**
 * Class NotificationServiceRepo
 * @package App\Services\Forus\Notification\Repositories
 */
class NotificationRepo implements INotificationRepo
{
    protected $typeModel;
    protected $preferencesModel;
    protected $unsubscriptionModel;
    protected $unsubTokenModel;

    /**
     * NotificationServiceRepo constructor.
     * @param NotificationType $typeModel
     * @param NotificationPreference $preferencesModel
     * @param NotificationUnsubscription $unsubscriptionModel
     * @param NotificationUnsubscriptionToken $unsubscriptionTokenModel
     */
    public function __construct(
        NotificationType $typeModel,
        NotificationPreference $preferencesModel,
        NotificationUnsubscription $unsubscriptionModel,
        NotificationUnsubscriptionToken $unsubscriptionTokenModel
    ) {
        $this->typeModel = $typeModel;
        $this->preferencesModel = $preferencesModel;
        $this->unsubscriptionModel = $unsubscriptionModel;
        $this->unsubTokenModel = $unsubscriptionTokenModel;
    }

    /**
     * Is email unsubscribed from all emails
     * @param string $email
     * @return bool
     */
    public function isEmailUnsubscribed(string $email): bool {
        return $this->unsubscriptionModel->newQuery()->where(
            compact('email'))->count() > 0;
    }

    /**
     * Check if Mail class can be unsubscribed
     * @param string $emailClass
     * @return bool
     */
    public function isMailUnsubscribable(string $emailClass): bool {
        $keys = array_flip($this->typeModel::getMailMap());
        $key = $keys[$emailClass];

        if (!isset($keys[$emailClass])) {
            return false;
        }

        return !in_array($key, $this->typeModel::getMandatoryMailKeys());
    }

    /**
     * Is email $emailClass unsubscribed
     * @param string $identity_address
     * @param string $emailClass
     * @return bool
     * @throws \Exception
     */
    public function isEmailTypeUnsubscribed(
        string $identity_address,
        string $emailClass
    ): bool {
        if (!$this->isMailUnsubscribable($emailClass)) {
            return false;
        }

        $notificationType = $this->typeModel->newQuery()->where([
            'key' => array_flip($this->typeModel::getMailMap())[$emailClass]
        ])->first();

        $notification_type_id = $notificationType->getKey();
        $subscribed = false;

        return $this->preferencesModel->newQuery()->where(compact(
            'identity_address', 'notification_type_id', 'subscribed'
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
        $model = $token ? $this->unsubTokenModel->findByToken($token) : null;

        return ($model ?: $this->unsubTokenModel->makeToken($email))->token;
    }

    /**
     * Unsubscribe email from all notifications
     * @param string $email
     */
    public function unsubscribeEmail(
        string $email
    ): void {
        $this->unsubscriptionModel->newQuery()->firstOrCreate(
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
        $this->unsubscriptionModel->where(compact('email'))->delete();
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
        $token = $this->unsubTokenModel->findByToken($token, $active);

        return $token ? $token->email : null;
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
        $keys = $this->typeModel->newQuery()->whereNotIn(
            'key',
            $this->typeModel::getMandatoryMailKeys()
        )->pluck('id', 'key');

        $unsubscribedKeys = $this->preferencesModel->where(compact(
            'identity_address', 'subscribed'
        ))->pluck('notification_type_id')->values();

        return $keys->map(function($id, $key) use ($unsubscribedKeys) {
            return [
                'key' => $key,
                'subscribed' => $unsubscribedKeys->search($id) === false
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
        /** @var NotificationType[]|Collection $types */
        $types = NotificationType::query()
            ->whereIn('key', array_keys($data))
            ->select('id', 'key')
            ->get();

        foreach ($types as $type) {
            logger()->debug(json_encode([
                'identity_address' => $identityAddress,
                'notification_type_id' => $type->id,
                'subscribed' => $data[$type->key]
            ], JSON_PRETTY_PRINT));
            $this->preferencesModel->newQuery()->updateOrCreate([
                'identity_address' => $identityAddress,
                'notification_type_id' => $type->id,
                'subscribed' => $data[$type->key]
            ]);
        }

        return $this->getNotificationPreferences($identityAddress);
    }

    /**
     * @return array
     */
    public function mailTypeKeys(): array
    {
        return array_values(array_diff(
            array_keys($this->typeModel::getMailMap()),
            $this->typeModel::getMandatoryMailKeys()
        ));
    }
}