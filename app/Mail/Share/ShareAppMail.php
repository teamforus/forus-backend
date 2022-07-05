<?php

namespace App\Mail\Share;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;

/**
 * Class ShareAppMail
 * @package App\Mail\Share
 */
class ShareAppMail extends ImplementationMail
{
    protected string $subjectKey = 'share/email.me_app_download_link.title';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('me_app_download_link');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        $link = env('ME_APP_SMS_DOWNLOAD_LINK', 'https://www.forus.io/DL');

        return [
            'download_link' => $this->makeLink($link, $link),
            'download_button' => $this->makeButton($link, $link),
        ];
    }
}
