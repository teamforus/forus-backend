<?php

namespace App\Mail\Funds;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use League\CommonMark\Exception\CommonMarkException;

class FundSponsorCustomNotificationMail extends ImplementationMail
{
    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildCustomMail(
            Arr::get($this->mailData, 'notification_subject'),
            Str::replace("&amp;nbsp;", "&nbsp;", Arr::get($this->mailData, 'notification_content')),
        );
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            'webshop_link' => $this->makeLink($data['webshop_link'], $data['webshop_link']),
            'webshop_button' => $this->makeButton($data['webshop_link'], 'Ga naar webshop'),
        ];
    }
}
