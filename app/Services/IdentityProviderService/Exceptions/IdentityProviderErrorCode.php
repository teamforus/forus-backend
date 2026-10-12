<?php

namespace App\Services\IdentityProviderService\Exceptions;

final class IdentityProviderErrorCode
{
    public const string FEATURE_DISABLED = 'feature_disabled';
    public const string ORGANIZATION_IDENTITY_PROVIDER_DISABLED = 'organization_identity_provider_disabled';
    public const string CONNECTION_DISABLED = 'connection_disabled';
    public const string MEMBERSHIP_INACTIVE = 'membership_inactive';
    public const string DASHBOARD_ROLE_MISSING = 'dashboard_role_missing';
    public const string WEBSHOP_NOT_ENABLED = 'webshop_not_enabled';
    public const string SESSION_EXPIRED = 'session_expired';
    public const string SESSION_REPLAYED = 'session_replayed';
    public const string TENANT_NOT_CONNECTED = 'tenant_not_connected';
    public const string CALLBACK_FAILED = 'callback_failed';
    public const string MODE_INVALID = 'mode_invalid';
    public const string LINK_NOT_FOUND = 'link_not_found';
    public const string CLAIMS_INVALID = 'claims_invalid';
    public const string ENTRA_ACCOUNT_STATUS_MISSING = 'entra_account_status_missing';
    public const string ENTRA_ACCOUNT_STATUS_INVALID = 'entra_account_status_invalid';
    public const string ENTRA_GUEST_NOT_SUPPORTED = 'entra_guest_not_supported';
    public const string ACCOUNT_LINK_UNAVAILABLE = 'account_link_unavailable';
    public const string ACCOUNT_LINK_INVALID = 'account_link_invalid';
    public const string ACCOUNT_LINK_CONFLICT = 'account_link_conflict';
    public const string LINK_NOT_ACTIVE = 'link_not_active';
    public const string ENTRA_ROLE_CLAIM_MISSING = 'entra_role_claim_missing';
    public const string ENTRA_ADMIN_ROLE_REQUIRED = 'entra_admin_role_required';
    public const string EXCHANGE_INVALID = 'exchange_invalid';
    public const string IDENTITY_MISSING = 'identity_missing';
    public const string ADMIN_CONSENT_OWNER_CHANGED = 'admin_consent_owner_changed';
    public const string CONNECTION_ALREADY_EXISTS = 'connection_already_exists';
    public const string ENTRA_SESSION_EXPIRED = 'entra_session_expired';
    public const string ENTRA_SESSION_INVALID = 'entra_session_invalid';
    public const string ENTRA_SESSION_NOT_FOUND = 'entra_session_not_found';
    public const string ENTRA_TENANT_ALREADY_CONNECTED = 'entra_tenant_already_connected';
    public const string TENANT_VERIFICATION_FAILED = 'tenant_verification_failed';
    public const string ADMIN_CONSENT_SESSION_REPLAYED = 'admin_consent_session_replayed';
    public const string ADMIN_CONSENT_SESSION_EXPIRED = 'admin_consent_session_expired';
    public const string CONNECTION_CHANGED_CONCURRENTLY = 'connection_changed_concurrently';
    public const string CONNECTION_NOT_ENABLED = 'connection_not_enabled';
    public const string CONNECTION_NOT_PAUSED = 'connection_not_paused';
    public const string CONNECTION_NOT_CURRENT = 'connection_not_current';
    public const string CONNECTION_DISCONNECTED = 'connection_disconnected';
    public const string ADMIN_CONSENT_DENIED = 'admin_consent_denied';
    public const string ADMIN_CONSENT_TENANT_MISSING = 'admin_consent_tenant_missing';
    public const string ADMIN_CONSENT_TENANT_MISMATCH = 'admin_consent_tenant_mismatch';
    public const string ADMIN_CONSENT_SESSION_NOT_FOUND = 'admin_consent_session_not_found';
    public const string ADMIN_CONSENT_CALLBACK_FAILED = 'admin_consent_callback_failed';
    public const string OIDC_START_FAILED = 'oidc_start_failed';
    public const string ACCOUNT_LINK_START_FAILED = 'account_link_start_failed';
    public const string ACCOUNT_LINK_COMPLETE_FAILED = 'account_link_complete_failed';
    public const string LOGIN_EXCHANGE_FAILED = 'login_exchange_failed';
    public const string UNEXPECTED_FAILURE = 'unexpected_failure';
    public const string ADMIN_CONSENT_START_FAILED = 'admin_consent_start_failed';
    public const string CONNECTION_PAUSE_FAILED = 'connection_pause_failed';
    public const string CONNECTION_RESUME_FAILED = 'connection_resume_failed';
    public const string CONNECTION_DISCONNECT_FAILED = 'connection_disconnect_failed';
}
