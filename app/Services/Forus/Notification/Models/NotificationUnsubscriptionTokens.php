<?php

namespace App\Models;

/**
 * App\Models\NotificationUnsubscriptionToken
 *
 * @property int $id
 * @property string $email
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscriptionToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscriptionToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscriptionToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscriptionToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscriptionToken whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscriptionToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscriptionToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationUnsubscriptionToken whereUpdatedAt($value)
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
    public function makeToken(string $email) {
        do {
            $token = resolve('token_generator')->generate(200);
        } while ($this->findByToken($token, false));

        return self::create(compact('email', 'token'));
    }

    /**
     * Find model by token
     * @param string $token
     * @param bool $onlyActive
     * @return NotificationUnsubscriptionToken|null
     */
    public function findByToken(string $token, bool $onlyActive = true) {
        $token = $this->where(compact('token'));

        if ($onlyActive) {
            $token->where('created_at', '>' , now()->subDays(7));
        }

        return $token->first();
    }
}
