<?php

namespace App\Services\MailDatabaseLoggerService;

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
        EmailLog::create([
            'from' => $this->formatAddressField($event->message, 'From'),
            'to' => $this->formatAddressField($event->message, 'To'),
            'cc' => $this->formatAddressField($event->message, 'Cc'),
            'bcc' => $this->formatAddressField($event->message, 'Bcc'),
            'subject' => $event->message->getSubject(),
            'body' => $event->message->toString(),
            'content' => $event->message->getHtmlBody() ?: $event->message->getTextBody(),
            'headers' => $event->message->getHeaders()->toString(),
            'mailable' => $event->data['mailable'] ?? null,
            'attachments' => $this->saveAttachments($event->message),
        ]);
    }

    /**
     * Format address strings for sender, to, cc, bcc.
     *
     * @param Email $message
     * @param string $field
     * @return null|string
     */
    function formatAddressField(Email $message, string $field): ?string
    {
        return $message->getHeaders()->get($field)?->getBodyAsString();
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
