<?php

namespace App\Services\MailDatabaseLoggerService\Traits;

use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use DOMDocument;
use DOMElement;

/**
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait AssertsSentEmails
{
    /**
     * @param string $email
     * @param Carbon|null $after
     * @return EmailLog|null
     */
    public function findFirstEmailRestoreEmail(
        string $email,
        ?Carbon $after = null
    ): ?EmailLog {
        return $this->emailsWithLink($email, 'identity/proxy/email/redirect', $after)->first();
    }

    /**
     * @param string $email
     * @param Carbon|null $after
     * @return EmailLog|null
     */
    public function findFirstEmailConfirmationEmail(
        string $email,
        ?Carbon $after = null
    ): ?EmailLog {
        return $this->emailsWithLink($email, 'identity/proxy/confirmation/redirect', $after)->first();
    }

    /**
     * @param string $email
     * @param Carbon|null $after
     * @return EmailLog|null
     */
    public function findFirstEmailVerificationEmail(
        string $email,
        ?Carbon $after = null
    ): ?EmailLog {
        return $this->emailsWithLink($email, 'email-verification', $after)->first();
    }

    /**
     * Assert that email confirmation was sent to the identity after given time
     *
     * @param string $email
     * @param string $mailable
     * @param Carbon|null $after
     * @return void
     */
    public function assertMailableSent(
        string $email,
        string $mailable,
        ?Carbon $after = null
    ): void {
        static::assertNotFalse(
            $this->getEmailOfTypeQuery($email, $mailable, $after)->exists(),
            "No '$mailable' mailable sent."
        );
    }

    /**
     * Assert that email confirmation was sent to the identity after given time
     *
     * @param string $email
     * @param Carbon|null $after
     * @return void
     */
    public function assertEmailRestoreLinkSent(
        string $email,
        ?Carbon $after = null
    ): void{
        static::assertNotNull(
            $this->findFirstEmailRestoreLink($email, $after),
            "No identity email restore link sent."
        );
    }

    /**
     * Assert that email confirmation was sent to the identity after given time
     *
     * @param string $email
     * @param Carbon|null $after
     * @return void
     */
    public function assertEmailConfirmationLinkSent(
        string $email,
        ?Carbon $after = null
    ): void{
        static::assertNotNull(
            $this->findFirstEmailConfirmationLink($email, $after),
            "No identity email confirmation link sent."
        );
    }

    /**
     * Assert that email verification was sent to the identity after given time
     *
     * @param string $email
     * @param Carbon|null $after
     * @return void
     */
    public function assertEmailVerificationLinkSent(
        string $email,
        ?Carbon $after = null
    ): void{
        static::assertNotNull(
            $this->findFirstEmailVerificationLink($email, $after),
            "No identity email verification link sent."
        );
    }

    /**
     * @param string $email
     * @param string $urlSubstr
     * @param Carbon|null $after
     * @return Collection|EmailLog[]
     */
    protected function emailsWithLink(
        string $email,
        string $urlSubstr,
        ?Carbon $after = null
    ): Collection|Arrayable {
        $emails = $this->getEmailQuery($email, $after)->get();

        return $emails->filter(function(EmailLog $emailLog) use ($urlSubstr) {
            return !empty($this->getEmailLink($emailLog->content, $urlSubstr));
        });
    }

    /**
     * @param string $content
     * @param string $urlSubstr
     * @return string|null
     */
    protected function getEmailLink(string $content, string $urlSubstr): ?string
    {
        return array_first(array_filter(
            $this->getEmailLinks($content),
            fn (string $link) => str_contains($link, $urlSubstr)
        ));
    }

    /**
     * @param string $content
     * @return array
     */
    protected function getEmailLinks(string $content): array
    {
        $htmlDom = new DOMDocument;

        $htmlDom->loadHTML($content);
        $links = $htmlDom->getElementsByTagName('a');
        $linksArray = [];

        /** @var DOMElement $link */
        foreach ($links as $link) {
            $linksArray[] = $link->getAttribute('href');
        }

        return $linksArray;
    }

    /**
     * @param string $email
     * @param string $mailable
     * @param Carbon|null $after
     * @return Builder
     */
    protected function getEmailOfTypeQuery(
        string $email,
        string $mailable,
        ?Carbon $after = null
    ): Builder {
        return $this->getEmailQuery($email, $after)->where(compact('mailable'));
    }

    /**
     * @param string $email
     * @param string $mailable
     * @param Carbon|null $after
     * @return Builder|Collection|EmailLog[]
     */
    protected function getEmailOfType(
        string $email,
        string $mailable,
        ?Carbon $after = null
    ): Builder|Collection|EmailLog {
        return $this->getEmailQuery($email, $after)->where(compact('mailable'))->get();
    }

    /**
     * @param mixed $email
     * @param string $subject
     * @param Carbon|null $after
     * @return Builder
     */
    protected function getFirstEmailsBySubjectQuery(
        mixed $email,
        string $subject,
        ?Carbon $after
    ): Builder {
        return $this->getEmailQuery($email, $after)->where(compact('subject'));
    }

    /**
     * @param string $email
     * @param Carbon|null $after
     * @return Builder
     */
    protected function getEmailQuery(string $email, ?Carbon $after = null): Builder
    {
        return EmailLog::where(function(Builder $builder) use ($email, $after) {
            if ($after) {
                $builder->where('created_at', '>=', $after);
            }

            $builder->where('to', $email);
        });
    }

    /**
     * @param mixed $email
     * @param Carbon|null $startTime
     * @return string|null
     */
    private function findFirstEmailConfirmationLink(mixed $email, ?Carbon $startTime): ?string
    {
        return $this->getEmailLink(
            $this->findFirstEmailConfirmationEmail($email, $startTime)?->content ?: '',
            'identity/proxy/confirmation/redirect'
        );
    }

    /**
     * @param mixed $email
     * @param Carbon|null $startTime
     * @return string|null
     */
    private function findFirstEmailRestoreLink(mixed $email, ?Carbon $startTime): ?string
    {
        return $this->getEmailLink(
            $this->findFirstEmailRestoreEmail($email, $startTime)?->content ?: '',
            'identity/proxy/email/redirect'
        );
    }

    /**
     * @param mixed $email
     * @param Carbon|null $startTime
     * @return string|null
     */
    private function findFirstEmailVerificationLink(mixed $email, ?Carbon $startTime): ?string
    {
        return $this->getEmailLink(
            $this->findFirstEmailVerificationEmail($email, $startTime)?->content ?: '',
            'email-verification'
        );
    }
}