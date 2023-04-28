<?php

namespace App\Mail\Digest;

/**
 * Class DigestProviderFundsMail
 * @package App\Mail\Digest
 */
class DigestProviderFundsMail extends BaseDigestMail
{
    protected ?string $preferencesLinkDashboard = 'provider';

    public function __construct($viewData = [])
    {
        parent::__construct($viewData);

        $this->subject(trans('digests/provider_funds.subject'));
    }
}
