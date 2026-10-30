<?php

namespace App\Modules\RequesterImport\Exceptions;

use LogicException;
use RuntimeException;
use Throwable;

final class RequesterImportException extends RuntimeException
{
    private const array ERRORS = [
        RequesterImportErrorCode::ROW_UNAVAILABLE => ['bsn', 'requester_import::validation.row_unavailable'],
        RequesterImportErrorCode::CLIENT_UUID_REQUIRED => ['client_uuid', 'requester_import::validation.client_uuid_required'],
        RequesterImportErrorCode::CLIENT_UUID_MISMATCH => ['client_uuid', 'requester_import::validation.client_uuid_mismatch'],
        RequesterImportErrorCode::CLIENT_UUID_CONFLICT => ['client_uuid', 'requester_import::validation.client_uuid_conflict'],
        RequesterImportErrorCode::CLIENT_UUID_IMMUTABLE => [
            'client_uuid', 'requester_import::validation.client_uuid_immutable',
        ],
    ];

    private string $field;

    /**
     * @param string $errorCode
     * @param Throwable|null $previous
     * @param int|null $rowId
     */
    public function __construct(
        protected string $errorCode,
        ?Throwable $previous = null,
        public readonly ?int $rowId = null,
    ) {
        [$this->field, $messageKey] = self::ERRORS[$errorCode] ?? throw new LogicException(
            sprintf('Unknown requester-import conflict: %s.', $errorCode),
        );

        parent::__construct(__($messageKey), 0, $previous);
    }

    /**
     * @return string
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, array<string>>
     */
    public function errors(): array
    {
        return [$this->field => [$this->getMessage()]];
    }
}
