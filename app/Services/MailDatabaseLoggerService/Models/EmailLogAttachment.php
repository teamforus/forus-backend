<?php

namespace App\Services\MailDatabaseLoggerService\Models;

use App\Services\MailDatabaseLoggerService\MailDatabaseLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Services\MailDatabaseLoggerService\Models\EmailLogAttachment.
 *
 * @property int $id
 * @property int $email_log_id
 * @property string $type
 * @property string $path
 * @property string $file_name
 * @property string|null $content_id
 * @property string $content_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\MailDatabaseLoggerService\Models\EmailLog $email_log
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment whereContentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment whereEmailLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailLogAttachment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailLogAttachment extends Model
{
    public const string TYPE_RAW = 'raw';
    public const string TYPE_ATTACHMENT = 'attachment';

    /**
     * @var string[]
     */
    protected $fillable = [
        'type', 'path', 'file_name', 'content_id', 'content_type',
    ];

    protected $hidden = [
        'type', 'path', 'file_name', 'content_id', 'content_type',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function email_log(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }

    /**
     * @return string
     */
    public function getBase64(): string
    {
        return implode('', [
            'data:' . $this->content_type . ';base64, ',
            base64_encode((MailDatabaseLogger::make()->storage()->get($this->path))),
        ]);
    }
}
