<?php

namespace App\Services\IdentityProviderService\Services;

use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Models\IdentityProviderAdminSession;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\OpenIdService\OpenIdException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class IdentityProviderLogService
{
    public const string OPERATION_OIDC_START = 'oidc_start';
    public const string OPERATION_OIDC_CALLBACK = 'oidc_callback';
    public const string OPERATION_LOGIN_EXCHANGE = 'login_exchange';
    public const string OPERATION_ACCOUNT_LINK_START = 'account_link_start';
    public const string OPERATION_ACCOUNT_LINK_COMPLETE = 'account_link_complete';
    public const string OPERATION_ACCOUNT_LINK_UNLINK = 'account_link_unlink';
    public const string OPERATION_ADMIN_CONSENT_START = 'admin_consent_start';
    public const string OPERATION_ADMIN_CONSENT_CALLBACK = 'admin_consent_callback';
    public const string OPERATION_TENANT_VERIFICATION_CALLBACK = 'tenant_verification_callback';
    public const string OPERATION_CONNECTION_PAUSE = 'connection_pause';
    public const string OPERATION_CONNECTION_RESUME = 'connection_resume';
    public const string OPERATION_CONNECTION_DISCONNECT = 'connection_disconnect';

    protected const array CONTEXT_KEYS = [
        'session_uid', 'organization_id', 'connection_id', 'membership_id', 'tenant_id', 'expected_tenant_id', 'mode',
        'provider_error_code', 'state_present', 'admin_consent', 'tenant_present',
        'role_claim_present', 'received_role_ids', 'http_status',
    ];

    /**
     * @param IdentityProviderAdminSession $session
     * @return array
     */
    public static function adminSessionContext(IdentityProviderAdminSession $session): array
    {
        return [
            'session_uid' => $session->uid,
            'organization_id' => $session->organization_id,
            'connection_id' => $session->connection_id,
            'expected_tenant_id' => $session->expected_tenant_id,
        ];
    }

    /**
     * @param Organization $organization
     * @param ?IdentityProviderConnection $connection
     * @return array
     */
    public static function ownerContext(
        Organization $organization,
        ?IdentityProviderConnection $connection,
    ): array {
        return [
            'session_uid' => null,
            'organization_id' => $organization->id,
            'connection_id' => $connection?->id,
            'tenant_id' => $connection?->tenant_id,
        ];
    }

    /**
     * @param IdentityProviderOidcSession $session
     * @return array
     */
    public static function sessionContext(IdentityProviderOidcSession $session): array
    {
        return [
            'session_uid' => $session->uid,
            'organization_id' => $session->connection?->organization_id,
            'connection_id' => $session->connection_id,
            'membership_id' => $session->membership_id,
            'tenant_id' => $session->connection?->tenant_id,
            'mode' => $session->mode,
        ];
    }

    /**
     * @param string $operation
     * @param Throwable $exception
     * @param array $context
     * @param string $errorCode
     * @return string
     */
    public function exceptionFailure(
        string $operation,
        Throwable $exception,
        array $context = [],
        string $errorCode = IdentityProviderErrorCode::UNEXPECTED_FAILURE,
    ): string {
        $cause = $exception;

        if ($exception instanceof IdentityProviderException) {
            $context = [...$exception->context(), ...$context];
            $errorCode = $exception->errorCode();
            $cause = $exception->getPrevious();
        }

        if (!$cause || ($cause instanceof OpenIdException && !$cause->getPrevious())) {
            return $this->protocolFailure($operation, $errorCode, $context);
        }

        return $this->unexpectedFailure($operation, $exception, $context, $errorCode);
    }

    /**
     * @param string $operation
     * @param string $errorCode
     * @param array $context
     * @return string
     */
    public function protocolFailure(string $operation, string $errorCode, array $context = []): string
    {
        $diagnosticId = Str::uuid()->toString();

        try {
            Log::channel((string) Config::get('identity_providers.log_channel', 'openid'))->warning(
                'Entra protocol request rejected.',
                $this->context($diagnosticId, $operation, $errorCode, $context),
            );
        } catch (Throwable) {
        }

        return $diagnosticId;
    }

    /**
     * @param string $operation
     * @param Throwable $exception
     * @param array $context
     * @param string $errorCode
     * @return string
     */
    protected function unexpectedFailure(
        string $operation,
        Throwable $exception,
        array $context = [],
        string $errorCode = IdentityProviderErrorCode::UNEXPECTED_FAILURE,
    ): string {
        $diagnosticId = Str::uuid()->toString();

        try {
            Log::channel((string) Config::get('identity_providers.log_channel', 'openid'))->error(
                'Unexpected Entra integration failure.',
                [
                    ...$this->context($diagnosticId, $operation, $errorCode, $context),
                    'exception_class' => $exception::class,
                    'previous_exception_class' => $exception->getPrevious()
                        ? $exception->getPrevious()::class
                        : null,
                    'exception' => $exception,
                ],
            );
        } catch (Throwable) {
            // Logging failures must not replace the original response.
        }

        return $diagnosticId;
    }

    /**
     * @param string $diagnosticId
     * @param string $operation
     * @param string $errorCode
     * @param array $context
     * @return array
     */
    protected function context(
        string $diagnosticId,
        string $operation,
        string $errorCode,
        array $context,
    ): array {
        $context = Arr::only($context, self::CONTEXT_KEYS);

        if (array_key_exists('provider_error_code', $context)) {
            $context['provider_error_code'] = $this->providerErrorCode($context['provider_error_code']);
        }

        if (is_array($context['received_role_ids'] ?? null)) {
            $context['received_role_ids'] = array_values(array_unique(array_map(
                fn (mixed $roleId) => strtolower((string) $roleId),
                array_slice($context['received_role_ids'], 0, 20),
            )));
        }

        return array_filter([
            'diagnostic_id' => $diagnosticId,
            'operation' => $operation,
            'error_code' => $errorCode,
            ...$context,
        ], fn (mixed $value) => $value !== null);
    }

    /**
     * @param mixed $errorCode
     * @return ?string
     */
    protected function providerErrorCode(mixed $errorCode): ?string
    {
        if (!is_string($errorCode) || $errorCode === '') {
            return null;
        }

        $errorCode = preg_replace('/[^a-zA-Z0-9_.-]/', '', $errorCode);

        return $errorCode === '' ? null : substr($errorCode, 0, 100);
    }
}
