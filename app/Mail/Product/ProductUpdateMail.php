<?php

namespace App\Mail\Product;

use App\Mail\ImplementationMail;
use Illuminate\Mail\Mailable;
use League\CommonMark\Exception\CommonMarkException;

class ProductUpdateMail extends ImplementationMail
{
    protected string $subjectKey = 'mails/system_mails.product_updated.title';

    /**
     * @return Mailable
     * @throws CommonMarkException
     */
    public function build(): Mailable
    {
        return $this->buildSystemMail('product_updated');
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        return [
            ...$data,
            'sponsor_dashboard_link' => $this->makeLink($data['sponsor_dashboard_link'], 'dashboard'),
            'sponsor_dashboard_button' => $this->makeLink($data['sponsor_dashboard_link'], 'dashboard'),
        ];
    }
}