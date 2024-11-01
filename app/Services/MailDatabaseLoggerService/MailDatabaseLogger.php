<?php

namespace App\Services\MailDatabaseLoggerService;

use App\Services\EventLogService\Models\EventLog;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use App\Services\MailDatabaseLoggerService\Models\EmailLogAttachment;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Mime\Email;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Contracts\Filesystem\Filesystem;

class MailDatabaseLogger
{
    protected string $storageDriver;
    protected string $storagePath;

    public function __construct()
    {
        $this->storageDriver = config('forus.mail.log_storage_driver');
        $this->storagePath = config('forus.mail.log_storage_path');
    }

    /**
     * @return static
     */
    public static function make(): static
    {
        return new static();
    }

    /**
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    /**
     * Handle the actual logging.
     *
     * @param MessageSending $event
     * @return void
     */
    public function handle(MessageSending $event): void
    {
        $eventLog = $event->data['eventLog'] ?? null;
        $systemNotificationKey = $event->data['notificationTemplateKey'] ?? null;

        $from = $event->message->getFrom()[0] ?? null;
        $to = $event->message->getTo()[0] ?? null;

        $emailLog = EmailLog::create([
            'from_name' => $from?->getName(),
            'from_address' => $from?->getAddress(),
            'to_name' => $to?->getName(),
            'to_address' => $to?->getAddress(),
            'subject' => $event->message->getSubject(),
            'content' => $event->message->getHtmlBody() ?: $event->message->getTextBody(),
            'headers' => $event->message->getHeaders()->toString(),
            'mailable' => $event->data['mailable'] ?? null,
            'event_log_id' => $eventLog instanceof EventLog ? $eventLog->id : null,
            'system_notification_key' => $systemNotificationKey,
        ]);

        if (Config::get('forus.mail.log_attachments')) {
            $this->appendAttachments($emailLog, $event->message);
        }

        $this->appendRaw($emailLog, $event->message);
    }

    /**
     * Collect all attachments and format them as strings.
     *
     * @param EmailLog $emailLog
     * @param Email $message
     * @return void
     */
    protected function appendAttachments(EmailLog $emailLog, Email $message): void
    {
        foreach ($message->getAttachments() as $attachment) {
            $path = $this->makeAttachmentPath(token_generator()->generate(64));
            $this->storage()->put($path, $attachment->getBody());

            $emailLog->email_log_attachments()->create([
                'path' => $path,
                'type' => EmailLogAttachment::TYPE_ATTACHMENT,
                'file_name' => $attachment->getFilename(),
                'content_id' => $attachment->getContentId(),
                'content_type' => $attachment->getContentType(),
            ]);
        }
    }

    /**
     * Collect all attachments and format them as strings.
     *
     * @param EmailLog $emailLog
     * @param Email $message
     * @return void
     */
    protected function appendRaw(EmailLog $emailLog, Email $message): void
    {
        $name = token_generator()->generate(64) . '.eml';
        $path = $this->makeAttachmentPath($name);
        $this->storage()->put($path, $message->toString());

        $emailLog->email_log_attachments()->create([
            'path' => $path,
            'type' => EmailLogAttachment::TYPE_RAW,
            'file_name' => $name,
            'content_id' => null,
            'content_type' => 'message/rfc822',
        ]);
    }

    /**
     * @return Filesystem
     */
    public function storage(): Filesystem
    {
        return resolve('filesystem')->disk($this->storageDriver);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function makeAttachmentPath(string $name): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            now()->startOfDay()->timestamp,
            $name,
        ]);
    }
}
