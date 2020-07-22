<?php

namespace App\Services\Forus\Identity\Models;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Services\Forus\Identity\Models\Identity
 *
 * @property int $id
 * @property string $pin_code
 * @property string $public_key
 * @property string $private_key
 * @property string|null $passphrase
 * @property string $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Identity\Models\IdentityEmail[] $emails
 * @property-read int|null $emails_count
 * @property-read string $email
 * @property-read \App\Services\Forus\Identity\Models\IdentityEmail $initial_email
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\App\Services\Forus\Identity\Models\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Services\Forus\Identity\Models\IdentityEmail $primary_email
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Identity\Models\IdentityProxy[] $proxies
 * @property-read int|null $proxies_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity wherePassphrase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity wherePinCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity wherePrivateKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\Identity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Identity extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pin_code', 'address', 'passphrase', 'private_key', 'public_key'
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'pin_code', 'passphrase', 'private_key', 'public_key'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emails(): HasMany
    {
        return $this->hasMany(IdentityEmail::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function primary_email(): HasOne
    {
        return $this->hasOne(IdentityEmail::class, 'identity_address', 'address')->where([
            'primary' => true
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function initial_email(): HasOne
    {
        return $this->hasOne(IdentityEmail::class, 'identity_address', 'address')->where([
            'initial' => true
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proxies(): HasMany
    {
        return $this->hasMany(IdentityProxy::class, 'identity_address', 'address');
    }

    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'identity_address', 'address');
    }

    /**
     * @param $address
     * @return Identity|\Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function findByAddress($address) {
        return self::whereAddress($address)->first();
    }

    /**
     * @param string $email
     * @param bool $verified
     * @param bool $primary
     * @param bool $initial
     * @return IdentityEmail
     */
    public function addEmail(
        string $email,
        bool $verified = false,
        bool $primary = false,
        bool $initial = false
    ): IdentityEmail {
        /** @var IdentityEmail $identityEmail */
        $identityEmail = $this->emails()->create(array_merge(compact(
            'email', 'verified', 'primary', 'initial'
        ), [
            'verification_token' => token_generator()->generate(200),
        ]));

        return $identityEmail;
    }

    /**
     * @return string
     */
    public function getEmailAttribute(): string
    {
        return $this->primary_email->email;
    }

    /**
     * @return string
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
