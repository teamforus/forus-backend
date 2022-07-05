<?php

namespace App\Mail;

use App\Models\Implementation;
use App\Models\NotificationTemplate;
use App\Models\SystemNotification;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

/**
 * Class ImplementationMail
 * @property string $email Destination email
 * @property string|null $identityId Destination email
 * @package App\Mail
 */
class ImplementationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ?EmailFrom $emailFrom = null;
    public ?string $implementationKey;
    public bool $informalCommunication = false;
    public string $communicationType;

    protected array $mailData = [];
    protected string $globalBuilderStyles = 'text_center';
    protected string $notificationTemplateKey;

    protected string $subjectKey = "";
    protected string $viewKey = "";

    /**
     * @var array|false|null
     */
    protected mixed $dataKeys = null;

    /**
     * @param array $data
     * @param EmailFrom|null $emailFrom
     */
    public function __construct(array $data = [], ?EmailFrom $emailFrom = null)
    {
        $this->setMailFrom($emailFrom ?: Implementation::general()->getEmailFrom());
        $this->mailData = $this->escapeData($data);
    }

    /**
     * @param EmailFrom|null $emailFrom
     */
    public function setMailFrom(?EmailFrom $emailFrom): void {
        $this->emailFrom = $emailFrom;
        $this->implementationKey = $emailFrom->getImplementationKey() ?: null;
        $this->informalCommunication = $emailFrom->isInformalCommunication();
        $this->communicationType = $this->informalCommunication ? 'informal' : 'formal';
    }

    /**
     * @return array
     */
    public function getTransData(): array
    {
        if (is_string($this->dataKeys) || is_array($this->dataKeys)) {
            return Arr::only($this->mailData, $this->dataKeys);
        }

        try {
            $logo = $this->headerIconImage($this->implementationLogoUrl());
        } catch (\Throwable) {}

        return array_merge($this->dataKeys === false ? [] : $this->mailData, [
            'email_logo' => $logo ?? '',
            'email_signature' => $this->implementationSignature(),
            'communicationType' => $this->communicationType,
            'implementationKey' => $this->implementationKey,
        ]);
    }

    /**
     * @return Mailable
     */
    public function buildBase(): Mailable
    {
        $data = $this->getTransData();
        $subject = $this->getSubject(trans($this->subjectKey, $data));

        return $this->from($this->emailFrom->getEmail(), $this->emailFrom->getName())
            ->with(compact('subject', 'data'))
            ->subject($subject)
            ->view($this->viewKey, $data);
    }

    /**
     * @param string|array|null $subject
     * @return string
     */
    protected function getSubject($subject = null): string
    {
        if (!$subject ?? false) {
            return config('app.name');
        }

        return (string) ($subject[$this->communicationType] ?? $subject);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function escapeData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (!ends_with($key, '_html')) {
                $data[$key] = e($value);
            }
        }

        ksort($data);

        return $data;
    }

    /**
     * @return Mailable
     */
    public function build(): Mailable
    {
        return $this->buildBase();
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return $data;
    }

    /**
     * Build the message.
     *
     * @return Mailable|null
     */
    public function buildNotificationTemplatedMail(): ?Mailable
    {
        $template = $this->implementationNotificationTemplate($this->notificationTemplateKey);

        if ($template) {
            $data = $this->getTransData();
            $data = array_merge($data, $this->getMailExtraData($data));
            $subject = $this->getSubject(str_var_replace(e($template->title), $data));

            $templateHtml = resolve('markdown.converter')->convert(e($template->content))->getContent();
            $templateHtml = str_var_replace($templateHtml, $data);

            $emailBody = new MailBodyBuilder();
            $emailBody->markdownHtml($templateHtml, $this->globalBuilderStyles, $this->implementationColor());

            $this->viewData['emailBody'] = $emailBody;

            return $this
                ->from($this->emailFrom->getEmail(), $this->emailFrom->getName())
                ->view('emails.mail-builder-template')
                ->subject($subject);
        }

        return null;
    }

    /**
     * @param string $template
     * @return Mailable
     */
    protected function buildSystemMail(string $template): Mailable
    {
        $data = $this->getTransData();
        $data = array_merge($data, $this->getMailExtraData($data));
        $builder = new MailBodyBuilder();

        $color = $this->implementationColor();
        $template = $this->implementationSystemTemplate($template);
        $emailBody = $builder->markdown($template, $data, 'text_center', $color);

        $this->viewData['emailBody'] = $emailBody;

        return $this
            ->from($this->emailFrom->getEmail(), $this->emailFrom->getName())
            ->view('emails.mail-builder-template')
            ->subject($this->getSubject(trans($this->subjectKey, $data)));
    }

    /**
     * @param string $url
     * @param string $text
     * @param string|null $color
     * @return string
     */
    protected function makeButton(string $url, string $text, ?string $color = null): string
    {
        $buttonStyle = config('forus.mail_styles.button_primary');
        $textCenterStyle = config('forus.mail_styles.text_center');

        $color = $color ?: $this->implementationColor();
        $buttonStyle = $color ? "$buttonStyle background-color: $color;" : $buttonStyle;
        $link = '<a href="' . $url . '" target="_blank" style="' . $buttonStyle . '">' . $text . '</a>';

        return '<div style="' . $textCenterStyle . '">' . $link .'</div>';
    }

    /**
     * @param string $url
     * @param string $text
     * @param string|null $color
     * @return string
     */
    protected function makeLink(string $url, string $text, ?string $color = null): string
    {
        $linkStyle = config('forus.mail_styles.link');
        $color = $color ?: $this->implementationColor();
        $linkStyle = $color ? "$linkStyle color: $color;" : $linkStyle;

        return '<a href="' . $url . '" target="_blank" style="' . $linkStyle . '">' . $text . '</a>';
    }

    /**
     * @param string $content
     * @param int $size
     * @return string
     */
    protected function makeQrCode(string $content, int $size = 300): string
    {
        $embed = 'embed:App\Mail\Models\EmbedQrCode:voucher-' . $content;
        $style = "display: block; margin: 0 auto; width: {$size}px;";

        return '<img style="' . $style . '" src="' . $embed . '" alt="" data-auto-embed>';
    }

    /**
     * @param string $url
     * @return string
     */
    protected function headerIconImage(string $url): string
    {
        return '<img src="' . $url . '" style="width: 300px; display: block; margin: 0 auto;" data-auto-embed>';
    }

    /**
     * @return string|null
     */
    protected function implementationKey(): ?string
    {
        return $this->implementationKey ?: $this->mailData['implementation_key'];
    }

    /**
     * @return string
     */
    protected function implementationLogoUrl(): string
    {
        $generalLogo = Implementation::general()->email_logo;
        $implementationLogo = Implementation::byKey($this->implementationKey())->email_logo;
        $emailLogo = $implementationLogo ?: $generalLogo;

        return $emailLogo->urlPublic('large');
    }

    /**
     * @return string
     */
    protected function implementationSignature(): string
    {
        $generalSignature = Implementation::general()->email_signature;
        $implementationSignature = Implementation::byKey($this->implementationKey())->email_signature;

        return ($implementationSignature ?: $generalSignature) ?: '';
    }

    /**
     * @return string
     */
    protected function implementationColor(): string
    {
        $generalColor = Implementation::general()->email_color;
        $implementationColor = Implementation::byKey($this->implementationKey())->email_color;

        return $implementationColor ?: $generalColor;
    }

    /**
     * @param string $templateFile
     * @return string
     */
    protected function implementationSystemTemplate(string $templateFile): string
    {
        $path = resource_path("mail_templates/$templateFile.md");
        $pathCommunication = resource_path("mail_templates/$templateFile.$this->communicationType.md");

        return file_get_contents(file_exists($pathCommunication) ? $pathCommunication : $path);
    }

    /**
     * @param string $key
     * @return NotificationTemplate|null
     */
    protected function implementationNotificationTemplate(string $key): ?NotificationTemplate
    {
        return SystemNotification::findTemplate($key, 'mail', $this->implementationKey());
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $e
     * @return void
     */
    public function failed(\Throwable $e): void
    {
        if ($logger = logger()) {
            $logger->error("Error sending digest: `" . $e->getMessage() . "`");
        }
    }
}
