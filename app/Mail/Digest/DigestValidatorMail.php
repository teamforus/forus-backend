<?php

namespace App\Mail\Digest;

/**
 * Class DigestValidatorMail
 * @package App\Mail\Digest
 */
class DigestValidatorMail extends BaseDigestMail
{
    protected ?string $preferencesLinkDashboard = 'validator';

    public function __construct($viewData = [])
    {
        parent::__construct($viewData);

        $this->subject(trans('digests/validator.subject'));
    }
}
