<?php

namespace App\Services\Forus\Notification\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Notification\Models\NotificationUnsubscriptionToken
 *
 * @property int $id
 * @property string $email
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUnsubscriptionToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUnsubscriptionToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUnsubscriptionToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUnsubscriptionToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUnsubscriptionToken whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUnsubscriptionToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUnsubscriptionToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUnsubscriptionToken whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationUnsubscriptionToken extends Model
{
    protected $fillable = [
        'email', 'token'
    ];

    /**
     * Make new model with unique token
     * @param string $email
     * @return NotificationUnsubscriptionToken
     */
    public static function makeToken(string $email): NotificationUnsubscriptionToken
    {
        do {
            $token = resolve('token_generator')->generate(200);
        } while (self::findByToken($token, false));

        return self::create(compact('email', 'token'));
    }

    /**
     * Find model by token
     *
     * @param string $token
     * @param bool $onlyActive
     * @return NotificationUnsubscriptionToken|null
     */
    public static function findByToken(string $token, bool $onlyActive = true): ?self
    {
        $token = self::query()->where(compact('token'));

        if ($onlyActive) {
            $token->where('created_at', '>' , now()->subDays(7));
        }

        return $token->first();
    }
}
