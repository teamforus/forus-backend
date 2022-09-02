<?php

namespace App\Services\MailDatabaseLoggerService\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\MailDatabaseLoggerService\Models\EmailLog
 *
 * @property int $id
 * @property string|null $from
 * @property string|null $to
 * @property string|null $cc
 * @property string|null $bcc
 * @property string|null $subject
 * @property string|null $body
 * @property string|null $content
 * @property string|null $headers
 * @property string|null $mailable
 * @property string|null $attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereBcc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereCc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereMailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'from', 'to', 'cc', 'bcc', 'subject', 'body', 'content', 'headers', 'mailable', 'attachments',
    ];

    protected $hidden = [
        'from', 'to', 'cc', 'bcc', 'subject', 'body', 'content', 'headers', 'mailable', 'attachments',
        'created_at', 'updated_at',
    ];
}
