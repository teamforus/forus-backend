<?php

namespace App\Services\IdentityProviderService\Exceptions;

use LogicException;
use Throwable;

final class IdentityProviderConflictException extends IdentityProviderException
{
    private const array MESSAGE_KEYS = [
        IdentityProviderErrorCode::CONNECTION_ALREADY_EXISTS => 'exceptions.identity_providers.connection_already_exists',
        IdentityProviderErrorCode::ENTRA_TENANT_ALREADY_CONNECTED => 'exceptions.identity_providers.entra_tenant_already_connected',
        IdentityProviderErrorCode::CONNECTION_CHANGED_CONCURRENTLY =>
            'exceptions.identity_providers.connection_changed_concurrently',
        IdentityProviderErrorCode::CONNECTION_NOT_ENABLED => 'exceptions.identity_providers.connection_not_enabled',
        IdentityProviderErrorCode::CONNECTION_NOT_PAUSED => 'exceptions.identity_providers.connection_not_paused',
        IdentityProviderErrorCode::CONNECTION_NOT_CURRENT => 'exceptions.identity_providers.connection_not_current',
    ];

    /**
     * @param string $errorCode
     * @param ?Throwable $previous
     * @param array $context
     */
    public function __construct(
        string $errorCode,
        ?Throwable $previous = null,
        array $context = [],
    ) {
        $messageKey = self::MESSAGE_KEYS[$errorCode] ?? throw new LogicException(
            sprintf('Unknown identity-provider conflict: %s.', $errorCode),
        );

        parent::__construct($errorCode, __($messageKey), $previous, $context);
    }
}
