<?php

namespace App\Services\IdentityBsnService\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class IdentityBsnException extends ConflictHttpException
{
    public const string AMBIGUOUS = 'bsn_identity_ambiguous';
    public const string ALREADY_ASSIGNED = 'bsn_already_assigned';

    /**
     * @param string $errorCode
     */
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct(match ($errorCode) {
            self::AMBIGUOUS => trans('identity.exceptions.bsn_ambiguous'),
            self::ALREADY_ASSIGNED => trans('identity.exceptions.bsn_already_assigned'),
        });
    }
}
