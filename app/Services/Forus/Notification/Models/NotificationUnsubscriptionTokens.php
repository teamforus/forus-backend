<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class NotificationUnsubscription
 * @property int $id
 * @property string $email
 * @property string $token
 * @property Carbon $created_at
 * * @property Carbon $updated_at
 * @package App\Models
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
