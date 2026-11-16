<?php

namespace App\Modules\RequesterImport\Exceptions;

use LogicException;
use RuntimeException;
use Throwable;

final class RequesterImportException extends RuntimeException
{
    private const array ERRORS = [
        RequesterImportErrorCode::MAPPING_UNAVAILABLE => [
            'requester_import_mapping_key', 'requester_import::validation.mapping_unavailable',
        ],
        RequesterImportErrorCode::MAPPING_COLUMNS_AMBIGUOUS => [
            'requester_import_mapping_key', 'requester_import::validation.mapping_columns_ambiguous',
        ],
        RequesterImportErrorCode::MAPPING_RECORDS_UNAVAILABLE => [
            'requester_import_mapping_key', 'requester_import::validation.mapping_records_unavailable',
        ],
        RequesterImportErrorCode::ROW_UNAVAILABLE => [
            'bsn', 'requester_import::validation.row_unavailable',
        ],
        RequesterImportErrorCode::IDENTITY_AMBIGUOUS => [
            'bsn', 'requester_import::validation.identity_ambiguous',
        ],
        RequesterImportErrorCode::PROFILE_MISMATCH => [
            'bsn', 'requester_import::validation.profile_mismatch',
        ],
        RequesterImportErrorCode::CLIENT_NUMBER_REQUIRED => [
            'client_number', 'requester_import::validation.client_number_required',
        ],
        RequesterImportErrorCode::CLIENT_NUMBER_MISMATCH => [
            'client_number', 'requester_import::validation.client_number_mismatch',
        ],
        RequesterImportErrorCode::CLIENT_NUMBER_CONFLICT => [
            'client_number', 'requester_import::validation.client_number_conflict',
        ],
        RequesterImportErrorCode::CLIENT_NUMBER_IMMUTABLE => [
            'client_number', 'requester_import::validation.client_number_immutable',
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
