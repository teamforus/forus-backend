<?php

namespace App\Modules\RequesterImport\Exceptions;

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
}
