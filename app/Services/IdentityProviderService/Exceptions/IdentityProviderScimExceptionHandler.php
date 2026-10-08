<?php

namespace App\Services\IdentityProviderService\Exceptions;

use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Responses\IdentityProviderScimResponse;
use App\Services\IdentityProviderService\Services\IdentityProviderLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class IdentityProviderScimExceptionHandler
{
    /**
     * @param Throwable $exception
     * @param Request $request
     * @return JsonResponse|null
     */
    public static function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (!self::handles($request)) {
            return null;
        }

        if ($exception instanceof IdentityProviderScimException) {
            return IdentityProviderScimResponse::error(
                $exception->status,
                $exception->getMessage(),
                $exception->scimType,
                $exception->diagnosticId ? ['X-Diagnostic-Id' => $exception->diagnosticId] : [],
            );
        }

        if ($exception instanceof ValidationException) {
            return IdentityProviderScimResponse::error(400, __('identity_provider.scim.invalid_query'), 'invalidValue');
        }

        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        return IdentityProviderScimResponse::error($status, match ($status) {
            404 => __('identity_provider.scim.not_found'),
            405 => __('identity_provider.scim.method_not_allowed'),
            429 => __('identity_provider.scim.rate_limited'),
            default => __('identity_provider.scim.request_failed'),
        }, headers: $headers);
    }

    /**
     * @param Throwable $exception
     * @param Request $request
     * @return bool|null
     */
    public static function report(Throwable $exception, Request $request): ?bool
    {
        if (!self::handles($request)) {
            return null;
        }

        $connection = $request->route('connection');

        resolve(IdentityProviderLogService::class)->exceptionFailure(
            IdentityProviderLogService::OPERATION_SCIM_REQUEST,
            $exception,
            $connection instanceof IdentityProviderConnection
                ? IdentityProviderLogService::ownerContext($connection->organization, $connection)
                : [],
            IdentityProviderErrorCode::SCIM_REQUEST_FAILED,
        );

        return false;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected static function handles(Request $request): bool
    {
        return $request->is('api/v1/scim/*/v2', 'api/v1/scim/*/v2/*');
    }
}
