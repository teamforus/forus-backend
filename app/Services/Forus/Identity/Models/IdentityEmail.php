<?php

namespace App\Services\Forus\Identity\Models;

use App\Models\Implementation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Services\Forus\Identity\Models\IdentityEmail
 *
 * @property int $id
 * @property string $email
 * @property string $identity_address
 * @property bool $verified
 * @property bool $primary
 * @property bool $initial
 * @property string $verification_token
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Identity\Models\Identity $identity
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail newQuery()
 * @method static \Illuminate\Database\Query\Builder|IdentityEmail onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail query()
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereInitial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail wherePrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereVerificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdentityEmail whereVerified($value)
 * @method static \Illuminate\Database\Query\Builder|IdentityEmail withTrashed()
 * @method static \Illuminate\Database\Query\Builder|IdentityEmail withoutTrashed()
 * @mixin \Eloquent
 */
class IdentityEmail extends Model
{
    use SoftDeletes;

    /**
     * @var array 
     */
    protected $fillable = [
        'email', 'identity_address', 'verified', 'primary', 'initial',
        'verification_token'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'initial' => 'bool',
        'primary' => 'bool',
        'verified' => 'bool',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * Send/Resend verification link to the email
     */
    public function sendVerificationEmail(): IdentityEmail
    {
        $notificationService = resolve('forus.services.notification');

        $notificationService->sendEmailVerificationLink(
            $this->email,
            Implementation::emailFrom(),
            url(sprintf('/email-verification/%s', $this->verification_token))
        );

        return $this;
    }

    /**
     * Make this identity email as primary
     */
    public function setPrimary(): IdentityEmail
    {
        $this->update([
            'primary' => true,
        ]);

        $this->identity->emails()->where('identity_emails.id', '!=', $this->id)->update([
            'primary' => false,
        ]);

        record_repo()->setIdentityPrimaryEmailRecord(
            $this->identity_address,
            $this->email
        );

        return $this;
    }

    /**
     * Set this identity email as verified
     * @return self
     */
    public function setVerified(): IdentityEmail
    {
        return tap($this)->update([
            'verified' => true
        ]);
    }

    /**
     * @param string $identity_address
     * @param string $type
     * @return string|null
     */
    public static function getEmailByAddress(
        string $identity_address, string $type = 'primary'
    ): ?string {
        $record = self::where('identity_address', $identity_address);

        if ($type === 'primary') {
            $record->where('primary', true);
        } elseif ($type === 'initial') {
            $record->where('initial', true);
        }

        return $record->first()->email ?? null;
    }
}
