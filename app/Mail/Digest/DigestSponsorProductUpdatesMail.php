<?php

namespace App\Mail\Digest;

class DigestSponsorProductUpdatesMail extends BaseDigestMail
{
    protected ?string $preferencesLinkDashboard = 'sponsor_product_updates';

    public function __construct($viewData = [])
    {
        parent::__construct($viewData);

        $this->subject(trans('digests.sponsor_product_updates.subject'));
    }
}
