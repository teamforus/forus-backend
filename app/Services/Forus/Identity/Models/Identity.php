<?php

namespace App\Services\Forus\Identity\Models;

use Illuminate\Database\Eloquent\Model;

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
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Identity\Models\IdentityProxy[] $proxies
 * @property-read int|null $proxies_count
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
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pin_code', 'address', 'passphrase', 'private_key', 'public_key'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emails() {
        return $this->hasMany(IdentityEmail::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proxies() {
        return $this->hasMany(IdentityProxy::class, 'identity_address', 'address');
    }

    /**
     * @param string $email
     * @param bool $verified
     * @param bool $primary
     * @return IdentityEmail
     */
    public function addEmail(
        string $email,
        bool $verified = false,
        bool $primary = false
    ): IdentityEmail {
        /** @var IdentityEmail $identityEmail */
        $identityEmail = $this->emails()->create(array_merge(compact(
            'email', 'verified', 'primary'
        ), [
            'verification_token' => token_generator()->generate(200),
        ]));

        return $identityEmail;
    }
}
