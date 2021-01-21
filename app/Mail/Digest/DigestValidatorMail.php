<?php

namespace App\Mail\Digest;

/**
 * Class DigestValidatorMail
 * @package App\Mail\Digest
 */
class DigestValidatorMail extends BaseDigestMail
{
    public function __construct($viewData = [])
    {
        parent::__construct($viewData);

        $this->subject(trans('digests/validator.subject'));
    }
}
