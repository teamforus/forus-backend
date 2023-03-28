<?php

namespace App\Mail\Digest;

/**
 * Class DigestProviderProductsMail
 * @package App\Mail\Digest
 */
class DigestProviderProductsMail extends BaseDigestMail
{
    protected ?string $preferencesLinkDashboard = 'provider';

    public function __construct($viewData = [])
    {
        parent::__construct($viewData);

        $this->subject(trans('digests/provider_products.subject'));
    }
}
