<?php

namespace App\Mail\Share;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ShareAppMail extends ImplementationMail
{
    public $subject = 'Download Me makkelijk via de link';

    /**
     * Build the message.
     *
     * @return $this
     * @throws CommonMarkException
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
