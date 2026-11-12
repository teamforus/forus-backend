<?php

namespace App\Modules\DCP\Services;

use App\Modules\DCP\Data\DCPResponseData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DCPLogService
{
    public const string OPERATION_SEND = 'send';
    public const string OPERATION_STATUS = 'status';
    public const string OPERATION_RECOVER = 'recover';
    public const string OPERATION_EVENT = 'event';

    /**
     * @param string $operation
     * @param int $organizationId
     * @param int|null $orderId
     * @param string $reason
     * @param Throwable|null $exception
     * @param DCPResponseData|null $response
     * @param int|null $bulkId
     * @return string
     */
    public function failure(
        string $operation,
        int $organizationId,
        ?int $orderId,
        string $reason,
        ?Throwable $exception = null,
        ?DCPResponseData $response = null,
        ?int $bulkId = null,
    ): string {
        $diagnosticId = (string) Str::uuid();

        try {
            Log::channel('dcp')->log($exception ? 'error' : 'warning', 'DCP operation failed.', [
                'diagnostic_id' => $diagnosticId,
                'operation' => $operation,
                'organization_id' => $organizationId,
                'order_id' => $orderId,
                'bulk_id' => $bulkId,
                'reason' => $reason,
                'http_status' => $response?->httpStatus,
                'remote_order_id' => $response?->remoteId,
                'correlation_id' => $response?->correlationId,
                'exception_class' => $exception ? $exception::class : null,
                'exception_file' => $exception?->getFile(),
                'exception_line' => $exception?->getLine(),
            ]);
        } catch (Throwable) {
            // Diagnostics must not change an order's submission outcome.
        }

        return $diagnosticId;
    }
}
