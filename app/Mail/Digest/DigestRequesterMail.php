<?php

namespace App\Mail\Digest;

/**
 * Class DigestRequesterMail
 * @package App\Mail\Digest
 */
class DigestRequesterMail extends BaseDigestMail
{
    public function __construct($viewData = [])
    {
        parent::__construct($viewData);

        $this->subject(trans('digests/requester.subject'));
    }
}
