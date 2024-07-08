<?php

namespace App\Services\MailDatabaseLoggerService\Models;

use App\Mail\Funds\FundRequestClarifications\FundRequestClarificationRequestedMail;
use App\Mail\Funds\FundRequestRecords\FundRequestRecordDeclinedMail;
use App\Mail\Funds\FundRequests\FundRequestApprovedMail;
use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Funds\FundRequests\FundRequestDeniedMail;
use App\Mail\Funds\FundRequests\FundRequestDisregardedMail;
use App\Services\EventLogService\Models\EventLog;
use Barryvdh\DomPDF\PDF;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Mews\Purifier\Facades\Purifier;

/**
 * App\Services\MailDatabaseLoggerService\Models\EmailLog
 *
 * @property int $id
 * @property int|null $event_log_id
 * @property string|null $from_name
 * @property string|null $from_address
 * @property string|null $to_name
 * @property string|null $to_address
 * @property string|null $subject
 * @property string|null $body
 * @property string|null $content
 * @property string|null $headers
 * @property string|null $mailable
 * @property string|null $attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read EventLog|null $event_log
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereEventLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereMailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereToAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereToName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailLog extends Model
{
    protected $fillable = [
        'from_name', 'from_address', 'to_name', 'to_address', 'subject', 'body',
        'content', 'headers', 'mailable', 'attachments', 'event_log_id',
    ];

    protected $hidden = [
        'from_name', 'from_address', 'to_name', 'to_address', 'subject', 'body',
        'content', 'headers', 'mailable', 'attachments', 'event_log_id',
    ];

    /**
     * @return BelongsTo
     */
    public function event_log(): BelongsTo
    {
        return $this->belongsTo(EventLog::class);
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
            FundRequestRecordDeclinedMail::class => 'fund_request_record_declined',
            FundRequestClarificationRequestedMail::class => 'fund_request_feedback_requested',
        ][$this->mailable] ?? null;
    }

    /**
     * @param bool $stripUnsubscribeLink
     * @param bool $insertStats
     * @return string
     */
    public function getContent(
        bool $stripUnsubscribeLink = true,
        bool $insertStats = false,
    ): string {
        $html = $stripUnsubscribeLink ?
            preg_replace('/(notifications\/unsubscribe\/)[a-zA-Z0-9]+/', '$1#', $this->content) :
            $this->content;


        $insert = view('emails.email-log-stats', ['emailLog' => $this])->toHtml();
        $pos = strrpos($html, '</html>');

        if ($insertStats && $pos !== false) {
            $html = substr_replace($html, $insert, $pos, 0);
        }

        return Purifier::clean($html, Config::get('forus.mail_purifier_config'));
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
}
