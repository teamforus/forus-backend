<?php

namespace App\Models;

use App\Mail\MailBodyBuilder;
use Barryvdh\DomPDF\PDF;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Throwable;

/**
 * @property int $id
 * @property int $identity_id
 * @property int|null $employee_id
 * @property string $mailable_type
 * @property int $mailable_id
 * @property string $type
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Identity $identity
 * @property-read Model|Eloquent $mailable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereIdentityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereMailableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereMailableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProviderMessage whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ProviderMessage extends Model
{
    public const string TYPE_APPROVE_RESERVATION = 'approve_reservation';
    public const string TYPE_CANCEL_RESERVATION = 'cancel_reservation';
    public const string TYPE_REJECT_RESERVATION = 'reject_reservation';
    public const string TYPE_REGULAR_MESSAGE = 'regular_message';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_id', 'type', 'message', 'mailable_type', 'mailable_id', 'employee_id',
    ];

    /**
     * @return MorphTo
     */
    public function mailable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @throws Throwable
     * @return PDF
     */
    public function toPdf(): PDF
    {
        $pdf = resolve('dompdf.wrapper');
        $pdf->loadHTML($this->getMessageHtml());

        return $pdf;
    }

    /**
     * @throws Throwable
     * @return string
     */
    public function getMessageHtml(): string
    {
        $emailBody = new MailBodyBuilder();
        $emailBody->markdownHtml($this->message, 'text_center');

        return view('emails.mail-builder-template', ['emailBody' => $emailBody, 'hideFooter' => true])->render();
    }
}
