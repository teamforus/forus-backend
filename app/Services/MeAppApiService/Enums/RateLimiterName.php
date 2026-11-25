<?php

namespace App\Services\MeAppApiService\Enums;

enum RateLimiterName: string
{
    case AuthEmail = 'mobile-auth-email';
    case AuthCreate = 'mobile-auth-create';
    case AuthApprove = 'mobile-auth-approve';
    case AuthExchange = 'mobile-auth-exchange';
    case AuthPoll = 'mobile-auth-poll';
    case AuthRedirect = 'mobile-auth-redirect';
    case IdentityRead = 'mobile-identity-read';
}
