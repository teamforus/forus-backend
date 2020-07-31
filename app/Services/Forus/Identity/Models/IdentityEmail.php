<?php

namespace App\Services\Forus\Identity\Models;

use App\Models\Implementation;
use Illuminate\Database\Eloquent\Model;
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
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Identity\Models\IdentityEmail onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail query()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereInitial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail wherePrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereVerificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereVerified($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Identity\Models\IdentityEmail withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Services\Forus\Identity\Models\IdentityEmail withoutTrashed()
 * @mixin \Eloquent
 * @property bool $initial
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereInitial($value)
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
    public function identity() {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * Send/Resend verification link to the email
     */
    public function sendVerificationEmail() {
        $notificationService = resolve('forus.services.notification');
        $notificationService->sendEmailVerificationLink(
            $this->email,
            Implementation::emailFrom(),
            url(sprintf('/email-verification/%s', $this->verification_token))
        );
    }

    /**
     * Make this identity email as primary
     */
    public function makePrimary() {
        $this->update([
            'primary' => true,
        ]);

        $this->identity->emails()->where(
            'identity_emails.id', '!=', $this->id
        )->update([
            'primary' => false,
        ]);

        record_repo()->setIdentityPrimaryEmailRecord(
            $this->identity_address,
            $this->email
        );
    }

    /**
     * Set this identity email as verified
     */
    public function setVerified() {
        $this->update([
            'verified' => true
        ]);
    }
}
