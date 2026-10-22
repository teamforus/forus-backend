<?php

namespace App\Services\IdentityProviderService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\EventLogService\Models\EventLog;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

/**
 * @property-read EventLog $resource
 */
class IdentityProviderEventResource extends BaseJsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $log = $this->resource;
        $errorCode = $log->data['error_code'] ?? null;
        $context = $log->data['context'] ?? [];
        $sso = in_array($log->event, IdentityProviderConnection::EVENTS_SSO, true);
        $employeeProvisioning = in_array($log->event, IdentityProviderConnection::EVENTS_EMPLOYEE_PROVISIONING, true);
        $requesterProvisioning = in_array($log->event, IdentityProviderConnection::EVENTS_REQUESTER_PROVISIONING, true);

        $category = match (true) {
            $sso => IdentityProviderConnection::EVENT_CATEGORY_SSO,
            $employeeProvisioning => IdentityProviderConnection::EVENT_CATEGORY_EMPLOYEE_PROVISIONING,
            $requesterProvisioning => IdentityProviderConnection::EVENT_CATEGORY_REQUESTER_PROVISIONING,
            default => null,
        };

        $provisioningContext = $employeeProvisioning || $requesterProvisioning ? $context : [];
        $reason = $provisioningContext['reason'] ?? null;

        $result = $provisioningContext['result'] ?? match ($log->event) {
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_CREATED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_CREATED => 'created',
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_REPROVISIONED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_REPROVISIONED => 'reprovisioned',
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_REACTIVATED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_REACTIVATED => 'reactivated',
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_DISABLED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_DISABLED => 'disabled',
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_DELETED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_DELETED => 'deleted',
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_UPDATED,
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_EMAIL_CHANGED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_UPDATED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_EMAIL_CHANGED => 'updated',
            default => null,
        };

        $fields = $provisioningContext['requested_fields'] ?? $provisioningContext['changed_fields'] ?? match ($log->event) {
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_REACTIVATED,
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_DISABLED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_REACTIVATED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_DISABLED => ['active'],
            IdentityProviderConnection::EVENT_SCIM_REQUESTER_EMAIL_CHANGED,
            IdentityProviderConnection::EVENT_SCIM_EMPLOYEE_EMAIL_CHANGED => ['emails'],
            default => [],
        };

        $nextAction = $reason ? match ($reason) {
            'email_conflict', 'username_conflict', 'account_conflict' => 'resolve_conflict',
            'connection_unavailable' => 'check_connection',
            'not_found' => 'check_account',
            'request_failed' => 'contact_support',
            default => 'check_mapping',
        } : null;

        return [
            'id' => $log->id,
            'event_type' => $log->event,
            'event_type_locale' => Lang::get("identity_provider.events.$log->event"),
            'category' => $category,
            'outcome' => $log->data['outcome'],
            'error_code' => $errorCode,
            'error_code_locale' => $reason
                ? Lang::get("identity_provider.scim.$reason")
                : ($errorCode ? Lang::get("identity_provider.errors.$errorCode") : null),
            'account' => $provisioningContext['account_email'] ?? $provisioningContext['external_id'] ?? null,
            'external_id' => $provisioningContext['external_id'] ?? null,
            'identity_id' => $provisioningContext['identity_id'] ?? null,
            'profile_id' => $provisioningContext['profile_id'] ?? null,
            'diagnostic_id' => $context['diagnostic_id'] ?? null,
            'requested_fields' => array_map(fn (string $field) => [
                'key' => $field,
                'name' => Lang::get('identity_provider.scim_fields.' . str_replace('.', '_', $field)),
            ], array_values(array_intersect($fields, IdentityProviderScimUserPayload::EVENT_FIELDS))),
            'result' => $result,
            'result_locale' => $result ? Lang::get("identity_provider.scim_results.$result") : null,
            'next_action_locale' => $nextAction ? Lang::get("identity_provider.scim_actions.$nextAction") : null,
            ...$this->makeTimestamps($log->only(['created_at'])),
        ];
    }
}
