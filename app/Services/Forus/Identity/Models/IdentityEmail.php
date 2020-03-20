<?php

namespace App\Services\Forus\Identity\Models;

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
 * @property string $verification_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Identity\Models\Identity $identity
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail wherePrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereVerificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Identity\Models\IdentityEmail whereVerified($value)
 * @mixin \Eloquent
 */
class IdentityEmail extends Model
{
    use SoftDeletes;

    /**
     * @var array 
     */
    protected $fillable = [
        'email', 'identity_address', 'verified', 'primary',
        'verification_token'
    ];

    /**
     * @var array
     */
    protected $casts = [
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
            url(sprintf('/email-verification/%s', $this->verification_token))
        );
    }

    /**
     *
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
    }
}
