<?php

namespace App\Mail\Digest;

/**
 * Class DigestSponsorMail
 * @package App\Mail\Digest
 */
class DigestSponsorMail extends BaseDigestMail
{
    protected ?string $preferencesLinkDashboard = 'sponsor';

    public function __construct($viewData = [])
    {
        parent::__construct($viewData);

        $this->subject(trans('digests/sponsor.subject'));
    }
}
