<?php

namespace App\Models;

use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

/**
 * App\Models\Identity2FA
 *
 * @property string $uuid
 * @property string $identity_address
 * @property string $state
 * @property int|null $auth_2fa_provider_id
 * @property string|null $phone
 * @property string|null $secret
 * @property string|null $secret_url
 * @property string $code
 * @property string $deactivation_code
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Auth2FAProvider|null $auth_2fa_provider
 * @property-read string $phone_masked
 * @property-read \App\Models\Identity $identity
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Identity2FACode[] $identity_2fa_codes
 * @property-read int|null $identity_2fa_codes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereAuth2faProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereDeactivationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereSecretUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Identity2FA withoutTrashed()
 * @mixin \Eloquent
 */
class Identity2FA extends Model
{
    use HasUuids, SoftDeletes;

    public const STATE_ACTIVE = 'active';
    public const STATE_PENDING = 'pending';
    public const STATE_DEACTIVATED = 'deactivated';

    protected $primaryKey = 'uuid';

    /**
     * @var string
     */
    protected $table = 'identity_2fa';

    /**
     * @var string[]
     */
    protected $fillable = [
        'identity_address', 'auth_2fa_provider_id', 'code', 'secret', 'secret_url', 'phone', 'state',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return BelongsTo
     */
    public function auth_2fa_provider(): BelongsTo
    {
        return $this->belongsTo(Auth2FAProvider::class, 'auth_2fa_provider_id', 'id');
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function identity_2fa_codes(): HasMany
    {
        return $this->hasMany(Identity2FACode::class, 'identity_2fa_uuid', 'uuid');
    }

    /**
     * @return bool
     * @namespace PhpUnused
     */
    public function isPending(): bool
    {
        return $this->state == self::STATE_PENDING;
    }

    /**
     * @return bool
     * @namespace PhpUnused
     */
    public function isActive(): bool
    {
        return $this->state == self::STATE_ACTIVE;
    }

    /**
     * @return bool
     * @namespace PhpUnused
     */
    public function isTypePhone(): bool
    {
        return $this->auth_2fa_provider->isTypePhone();
    }

    /**
     * @return bool
     * @namespace PhpUnused
     */
    public function isTypeAuthenticator(): bool
    {
        return $this->auth_2fa_provider->isTypeAuthenticator();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getPhoneMaskedAttribute(): string
    {
        return substr($this->phone,0,2)
            .str_repeat( '*', (strlen($this->phone) - 4))
            .substr($this->phone, -2);
    }

    /**
     * @return $this
     */
    public function activate(string $code, Auth2FAProvider $provider): self
    {
        $this->forceFill([
            'code' => $code,
            'state' => self::STATE_ACTIVE,
            'auth_2fa_provider_id' => $provider->id,
        ])->save();

        return $this->refresh();
    }

    /**
     * @param string $code
     * @return bool
     */
    public function deactivate(string $code): bool
    {
        $this->forceFill([
            'deactivation_code' => $code,
            'state' => self::STATE_DEACTIVATED,
        ])->save();

        return $this->delete();
    }

    /**
     * @param string $code
     * @return bool
     */
    public function deactivateCode(string $code): bool
    {
        return $this->identity_2fa_codes()->where('code', $code)->delete();
    }

    /**
     * @return string
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function makeAuthenticatorCode(): string
    {
        return (new Google2FA())->getCurrentOtp($this->secret);
    }

    /**
     * @return Identity2FACode
     * @throws \Throwable
     */
    public function makePhoneCode(): Identity2FACode
    {
        do {
            $code = random_int(111111, 999999);
        } while ($this->identity_2fa_codes()
            ->where(compact('code'))
            ->where('expire_at', '>', now())
            ->exists()
        );

        return $this->identity_2fa_codes()->create([
            'code' => $code,
            'expire_at' => Carbon::now()->addMinutes(
                Config::get('forus.auth_2fa.phone_code_validity_in_minutes'),
            ),
        ]);
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    public function sendCode(): bool
    {
        $this->identity_2fa_codes()->update([
            'state' => Identity2FACode::STATE_DEACTIVATED
        ]);

        return resolve('forus.services.sms_notification')->sendSms(
            'Confirmation code: ' . $this->makePhoneCode()->code,
            $this->phone,
        );
    }
}
