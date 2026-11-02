<?php

namespace App\Modules\RequesterImport\Services;

use App\Modules\RequesterImport\Exceptions\RequesterImportErrorCode;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RequesterImportLogService
{
    public const string OPERATION_IMPORT = 'import';
    public const string OPERATION_UPDATE = 'update';
    public const string OPERATION_SYNC = 'sync';

    private const array CONTEXT_KEYS = ['organization_id', 'employee_id', 'row_id', 'row_index'];

    /**
     * @param string $operation
     * @param Throwable $exception
     * @param array $context
     * @param string $fallbackCode
     * @return string
     */
    public function failure(string $operation, Throwable $exception, array $context, string $fallbackCode): string
    {
        $diagnosticId = Str::uuid()->toString();
        $expected = $exception instanceof RequesterImportException ||
            $exception instanceof ValidationException ||
            $exception instanceof UniqueConstraintViolationException || $exception instanceof ModelNotFoundException;

        $errorCode = RequesterImportErrorCode::fromException($exception, $fallbackCode);

        if ($exception instanceof RequesterImportException && $exception->rowId !== null) {
            $context['row_id'] = $exception->rowId;
        }

        try {
            Log::channel('requester_imports')->log(
                $expected ? 'warning' : 'error',
                $expected ? 'Requester import operation rejected.' : 'Unexpected requester import failure.',
                array_filter([
                    ...Arr::only($context, self::CONTEXT_KEYS),
                    'diagnostic_id' => $diagnosticId,
                    'operation' => $operation,
                    'error_code' => $errorCode,
                    'exception_class' => $exception::class,
                    'exception_file' => $exception->getFile(),
                    'exception_line' => $exception->getLine(),
                    'previous_exception_class' => $exception->getPrevious() ? $exception->getPrevious()::class : null,
                ], fn (mixed $value) => $value !== null),
            );
        } catch (Throwable) {
            // Logging failures must not replace the original response.
        }

        return $diagnosticId;
    }
}
