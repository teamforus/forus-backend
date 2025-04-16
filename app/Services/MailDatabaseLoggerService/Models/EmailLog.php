<?php

namespace App\Services\MailDatabaseLoggerService\Models;

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Mail\Funds\FundRequests\FundRequestDisregardedMail;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\ProductReservation;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Barryvdh\DomPDF\PDF;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

/**
 * App\Services\MailDatabaseLoggerService\Models\EmailLog.
 *
 * @property int $id
 * @property int|null $event_log_id
 * @property string|null $system_notification_key
 * @property string|null $from_name
 * @property string|null $from_address
 * @property string|null $to_name
 * @property string|null $to_address
 * @property string|null $subject
 * @property string|null $content
 * @property string|null $headers
 * @property string|null $mailable
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MailDatabaseLoggerService\Models\EmailLogAttachment[] $email_log_attachments
 * @property-read int|null $email_log_attachments_count
 * @property-read EventLog|null $event_log
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereEventLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereMailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereSystemNotificationKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereToAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereToName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailLog extends Model
{
    protected $fillable = [
        'from_name', 'from_address', 'to_name', 'to_address', 'subject', 'content',
        'headers', 'mailable', 'event_log_id', 'system_notification_key',
    ];

    protected $hidden = [
        'from_name', 'from_address', 'to_name', 'to_address', 'subject', 'content',
        'headers', 'mailable', 'event_log_id', 'system_notification_key',
    ];

    /**
     * @return BelongsTo
     */
    public function event_log(): BelongsTo
    {
        return $this->belongsTo(EventLog::class);
    }

    /**
     * @return HasMany
     */
    public function email_log_attachments(): HasMany
    {
        return $this->hasMany(EmailLogAttachment::class);
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return [
            FundRequestDeniedMail::class => 'fund_request_denied',
            FundRequestCreatedMail::class => 'fund_request_created',
            FundRequestApprovedMail::class => 'fund_request_approved',
            FundRequestDisregardedMail::class => 'fund_request_disregarded',
            FundRequestClarificationRequestedMail::class => 'fund_request_feedback_requested',
        ][$this->mailable] ?? null;
    }

    /**
     * @param bool $stripUnsubscribeLink
     * @param bool $insertStats
     * @param bool $addBase64
     * @return string
     */
    public function getContent(
        bool $stripUnsubscribeLink = true,
        bool $insertStats = false,
        bool $addBase64 = false,
    ): string {
        $html = $this->content;

        $html = $stripUnsubscribeLink ? $this->removeUnsubscriptionUrl($html) : $html;
        $html = $insertStats ? $this->addEmailStats($html) : $html;
        $html = $addBase64 ? $this->addBase64Attachments($html) : $html;

        return Purifier::clean($html, Config::get('forus.purifier.purifier_mail_config'));
    }

    /**
     * @param bool $stripUnsubscribeLink
     * @param bool $insertStats
     * @return PDF
     */
    public function toPdf(
        bool $stripUnsubscribeLink = true,
        bool $insertStats = true,
    ): PDF {
        $pdf = resolve('dompdf.wrapper');
        $pdf->loadHTML($this->getContent($stripUnsubscribeLink, $insertStats));

        return $pdf;
    }

    /**
     * @param string $html
     * @return string
     */
    protected function removeUnsubscriptionUrl(string $html): string
    {
        return preg_replace('/(notifications\/unsubscribe\/)[a-zA-Z0-9]+/', '$1#', $html);
    }

    /**
     * @param string $html
     * @return string
     */
    protected function addBase64Attachments(string $html): string
    {
        $attachments = $this
            ->email_log_attachments
            ->where('type', EmailLogAttachment::TYPE_ATTACHMENT);

        foreach ($attachments as $attachment) {
            $html = Str::replace("cid:$attachment->file_name", $attachment->getBase64(), $html);
        }

        return $html;
    }

    /**
     * @param string $html
     * @return string
     */
    protected function addEmailStats(string $html): string
    {
        $insert = view('emails.email-log-stats', ['emailLog' => $this])->toHtml();
        $pos = strrpos($html, '</html>');

        return $pos !== false ? substr_replace($html, $insert, $pos, 0) : $html;
    }

    /**
     * @return Model|FundRequest|null
     */
    public function getRelatedFundRequest(): Model|FundRequest|null
    {
        if ($this->event_log->loggable instanceof FundRequest) {
            return $this->event_log->loggable;
        }

        if ($this->event_log->loggable instanceof FundRequestRecord) {
            return $this->event_log->loggable->fund_request;
        }

        return null;
    }

    /**
     * @return Identity|null
     */
    public function getRelatedIdentity(): ?Identity
    {
        if ($this->event_log->loggable instanceof Voucher) {
            return $this->event_log->loggable->identity;
        }

        if (
            $this->event_log->loggable instanceof ProductReservation ||
            $this->event_log->loggable instanceof Reimbursement
        ) {
            return $this->event_log->loggable->voucher->identity;
        }

        return $this->getRelatedFundRequest()?->identity;
    }
}
