<?php

namespace App\Services\MailDatabaseLoggerService;

use App\Services\EventLogService\Models\EventLog;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Illuminate\Mail\Events\MessageSending;

class MailDatabaseLogger
{
    /**
     * Handle the actual logging.
     *
     * @param MessageSending $event
     * @return void
     */
    public function handle(MessageSending $event): void
    {
        $eventLog = $event->data['eventLog'] ?? null;

        $from = $event->message->getFrom()[0] ?? null;
        $to = $event->message->getTo()[0] ?? null;

        EmailLog::create([
            'from_name' => $from?->getName(),
            'from_address' => $from?->getAddress(),
            'to_name' => $to?->getName(),
            'to_address' => $to?->getAddress(),
            'subject' => $event->message->getSubject(),
            'body' => $event->message->toString(),
            'content' => $event->message->getHtmlBody() ?: $event->message->getTextBody(),
            'headers' => $event->message->getHeaders()->toString(),
            'mailable' => $event->data['mailable'] ?? null,
            'event_log_id' => $eventLog instanceof EventLog ? $eventLog->id : null,
            'attachments' => $this->saveAttachments($event->message),
        ]);
    }

    /**
     * Collect all attachments and format them as strings.
     *
     * @param Email $message
     * @return string|null
     */
    protected function saveAttachments(Email $message): ?string
    {
        if (!env('ENABLE_EMAIL_LOG_ATTACHMENTS', false) || empty($message->getAttachments())) {
            return null;
        }

        return collect($message->getAttachments())
            ->map(fn(DataPart $part) => $part->toString())
            ->implode("\n\n");
    }
}
