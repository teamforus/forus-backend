<?php

namespace App\Modules\RequesterImport\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Throwable;

final class RequesterImportErrorCode
{
    public const string ROW_UNAVAILABLE = 'row_unavailable';
    public const string IDENTITY_AMBIGUOUS = 'identity_ambiguous';
    public const string PROFILE_MISMATCH = 'profile_mismatch';
    public const string CLIENT_UUID_REQUIRED = 'client_uuid_required';
    public const string CLIENT_UUID_MISMATCH = 'client_uuid_mismatch';
    public const string CLIENT_UUID_CONFLICT = 'client_uuid_conflict';
    public const string CLIENT_UUID_IMMUTABLE = 'client_uuid_immutable';
    public const string DUPLICATE_IDENTIFIER = 'duplicate_identifier';
    public const string IMPORT_FAILED = 'import_failed';
    public const string UPDATE_FAILED = 'update_failed';
    public const string SYNC_FAILED = 'sync_failed';
    public const string VALIDATION_FAILED = 'validation_failed';

    /**
     * @param Throwable $exception
     * @param string $fallbackCode
     * @return string
     */
    public static function fromException(Throwable $exception, string $fallbackCode): string
    {
        return match (true) {
            $exception instanceof RequesterImportException => $exception->errorCode(),
            $exception instanceof ValidationException => self::VALIDATION_FAILED,
            $exception instanceof ModelNotFoundException => self::ROW_UNAVAILABLE,
            $exception instanceof UniqueConstraintViolationException => self::DUPLICATE_IDENTIFIER,
            default => $fallbackCode,
        };
    }
}
