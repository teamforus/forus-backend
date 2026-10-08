<?php

namespace App\Services\IdentityProviderService\Services;

use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderScimException;
use App\Services\IdentityProviderService\Models\IdentityProviderAdminSession;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use App\Services\OpenIdService\OpenIdException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
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
    public const string OPERATION_SCIM_CREDENTIAL_ISSUE = 'scim_credential_issue';
    public const string OPERATION_SCIM_CREDENTIAL_REVOKE = 'scim_credential_revoke';
    public const string OPERATION_SCIM_REQUEST = 'scim_request';

    protected const array CONTEXT_KEYS = [
        'session_uid', 'organization_id', 'connection_id', 'membership_id', 'tenant_id', 'expected_tenant_id', 'mode',
        'provider_error_code', 'state_present', 'admin_consent', 'tenant_present',
        'role_claim_present', 'received_role_ids', 'http_status', 'validation_errors',
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
     * @param IdentityProviderConnection $connection
     * @param string $method
     * @param array $payload
     * @param string|null $uid
     * @param Throwable $exception
     * @return IdentityProviderScimException
     */
    public function scimMutationFailure(
        IdentityProviderConnection $connection,
        string $method,
        array $payload,
        ?string $uid,
        Throwable $exception,
    ): IdentityProviderScimException {
        if ($exception instanceof IdentityProviderScimException) {
            $expected = true;
            $status = $exception->status;
            $scimType = $exception->scimType;
            $message = $exception->getMessage();
            $reason = $exception->reason ?? match ($scimType) {
                'invalidSyntax' => 'invalid_patch',
                'invalidPath' => 'invalid_patch_path',
                'mutability' => 'immutable_attribute',
                default => 'invalid_user',
            };
        } elseif ($exception instanceof ModelNotFoundException) {
            $expected = true;
            $status = 404;
            $scimType = null;
            $message = __('identity_provider.scim.not_found');
            $reason = 'not_found';
        } else {
            $expected = false;
            $status = 500;
            $scimType = null;
            $message = __('identity_provider.scim.request_failed');
            $reason = 'request_failed';
        }

        $context = [
            'connection_id' => $connection->id,
            'organization_id' => $connection->organization_id,
            'http_status' => $status,
            'validation_errors' => $exception instanceof IdentityProviderScimException
                ? $exception->validationErrors ?: null
                : null,
        ];

        $diagnosticId = $expected
            ? $this->protocolFailure(
                self::OPERATION_SCIM_REQUEST,
                IdentityProviderErrorCode::SCIM_REQUEST_FAILED,
                $context
            )
            : $this->exceptionFailure(
                self::OPERATION_SCIM_REQUEST,
                $exception,
                $context,
                IdentityProviderErrorCode::SCIM_REQUEST_FAILED,
            );

        try {
            $membership = $uid ? IdentityProviderMembership::where('connection_id', $connection->id)
                ->where('account_type', IdentityProviderMembership::ACCOUNT_TYPE_REQUESTER)
                ->where('uid', $uid)->first() : null;

            $connection->recordEvent(
                match ($method) {
                    'POST' => IdentityProviderConnection::EVENT_SCIM_USER_CREATE_FAILED,
                    'DELETE' => IdentityProviderConnection::EVENT_SCIM_USER_DELETE_FAILED,
                    default => IdentityProviderConnection::EVENT_SCIM_USER_UPDATE_FAILED,
                },
                outcome: IdentityProviderConnection::EVENT_OUTCOME_FAILURE,
                membership: $membership,
                errorCode: IdentityProviderErrorCode::SCIM_REQUEST_FAILED,
                context: [
                    ...IdentityProviderScimUserPayload::eventContext($payload),
                    'diagnostic_id' => $diagnosticId,
                    'method' => $method,
                    'reason' => $reason,
                    'result' => $expected ? 'unchanged' : 'unknown',
                ],
            );
        } catch (Throwable $auditException) {
            $this->exceptionFailure(self::OPERATION_SCIM_REQUEST, $auditException, $context);
        }

        return new IdentityProviderScimException(
            $message,
            $status,
            $scimType,
            $reason,
            $diagnosticId,
            $exception,
            validationErrors: $exception instanceof IdentityProviderScimException ? $exception->validationErrors : [],
        );
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
        $cause = $exception;

        while ($cause && !($cause instanceof QueryException)) {
            $cause = $cause->getPrevious();
        }

        try {
            Log::channel((string) Config::get('identity_providers.log_channel', 'openid'))->error(
                'Unexpected Entra integration failure.',
                [
                    ...$this->context($diagnosticId, $operation, $errorCode, $context),
                    'exception_class' => $exception::class,
                    'previous_exception_class' => $exception->getPrevious()
                        ? $exception->getPrevious()::class
                        : null,
                    ...$cause instanceof QueryException ? [
                        'query_exception_class' => $cause::class,
                        'sql_state' => (string) $cause->getCode(),
                    ] : [
                        'exception' => $exception,
                    ],
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
