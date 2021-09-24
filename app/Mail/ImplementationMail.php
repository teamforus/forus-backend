<?php

namespace App\Mail;

use App\Models\Implementation;
use App\Models\SystemNotification;
use App\Services\Forus\Notification\EmailFrom;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Class ImplementationMail
 * @property string $email Destination email
 * @property string|null $identityId Destination email
 * @package App\Mail
 */
class ImplementationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailFrom;
    public $implementationKey;
    public $informalCommunication;
    public $communicationType;

    protected $mailData = [];
    protected $globalBuilderStyles = 'text_center';
    protected $notificationTemplateKey;

    protected $subjectKey = "";
    protected $viewKey = "";

    /**
     * @var array|false|null
     */
    protected $dataKeys = null;

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

        return array_merge($this->dataKeys === false ? [] : $this->mailData, [
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
     * Build the message.
     *
     * @return $this
     */
    public function buildTemplatedNotification(array $extraData = []): Mailable
    {
        $template = SystemNotification::findTemplate(
            $this->notificationTemplateKey,
            'mail',
            $this->implementationKey ?: $this->mailData['implementation_key']
        );

        if ($template) {
            $data = array_merge($this->getTransData(), $extraData);
            $subject = $this->getSubject(str_var_replace($template->title, $data));
            $templateHtml = resolve('markdown')->convertToHtml($template->content);
            $templateHtml = str_var_replace($templateHtml, $data);

            $emailBody = new MailBodyBuilder();
            $emailBody->markdownHtml($templateHtml, $this->globalBuilderStyles);

            $this->viewData['emailBody'] = $emailBody;

            return $this->view('emails.mail-builder-template')->subject($subject);
        }
    }

    /**
     * @param string $url
     * @param string $text
     * @return string
     */
    protected function makeButton(string $url, string $text): string
    {
        $buttonStyle = config('forus.mail_styles.button_primary');
        $textCenterStyle = config('forus.mail_styles.text_center');
        $link = '<a href="' . $url . '" target="_blank" style="' . $buttonStyle . '">' . $text . '</a>';

        return '<div style="' . $textCenterStyle . '">' . $link .'</div>';
    }

    /**
     * @param string $content
     * @param int $size
     * @return string
     */
    protected function makeQrCode(string $content, int $size = 300): string
    {
        $data = make_qr_code('voucher', $content ?? '', $size);
        $base64 = 'data:image/png;base64,' . base64_encode($data);
        $style = "display: block; margin: 0 auto; width: {$size}px;";

        return "<img style=\"$style\" src=\"$base64\" alt=\"\">";
    }

    /**
     * @param string $url
     * @param string $text
     * @return string
     */
    protected function makeLink(string $url, string $text): string
    {
        $linkStyle = config('forus.mail_styles.link');

        return '<a href="' . $url . '" target="_blank" style="' . $linkStyle . '">' . $text . '</a>';
    }

    /**
     * @param string $key
     * @return string
     */
    protected function headerIcon(string $key): string
    {
        $imageHeader = mail_config($key, null, $this->implementationKey);

        return '<img src="' . $imageHeader . '" style="width: 297px; display: block; margin: 0 auto;">';
    }
}
