<?php

namespace App\Services\MeAppApiService;

use App\Models\Implementation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View as ViewFacade;
use LogicException;

class MeAppApiService
{
    /**
     * @param string|null $clientType
     * @return string|null
     */
    public function appKeyForClientType(?string $clientType): ?string
    {
        return match ($clientType) {
            Implementation::ME_APP_REQUESTER_ANDROID, Implementation::ME_APP_REQUESTER_IOS => 'requester',
            Implementation::ME_APP_PROVIDER_ANDROID, Implementation::ME_APP_PROVIDER_IOS => 'provider',
            default => null,
        };
    }

    /**
     * @param string|null $clientType
     * @throws LogicException
     * @return string
     */
    public function deepLinkBaseUrl(?string $clientType): string
    {
        $appKey = $this->appKeyForClientType($clientType);

        if (!$appKey) {
            return (string) Config::get('forus.front_ends.app-me_app');
        }

        $url = match ($appKey) {
            'requester' => Config::get('me-app-api.requester.deep_link_url'),
            'provider' => Config::get('me-app-api.provider.deep_link_url'),
        };

        if (!is_string($url) || $url === '') {
            throw new LogicException('The mobile app deep-link URL is not configured.');
        }

        return str_ends_with($url, '/') ? $url : $url . '/';
    }

    /**
     * @param string|null $clientType
     * @param bool $confirmation
     * @param array $parameters
     * @return string|null
     */
    public function emailRedirectUrl(?string $clientType, bool $confirmation, array $parameters): ?string
    {
        $appKey = $this->appKeyForClientType($clientType);

        if (!$appKey) {
            return null;
        }

        $action = $confirmation ? 'confirmation' : 'email';

        return URL::route("mobile.$appKey.v1.identity.proxy.$action.redirect", $parameters);
    }

    /**
     * @param string $emailToken
     * @param string|null $clientType
     * @param string|null $implementationKey
     * @param string|null $target
     * @return View
     */
    public function emailSignInView(
        string $emailToken,
        ?string $clientType,
        ?string $implementationKey,
        ?string $target = null,
    ): View {
        $redirectUrl = sprintf(
            $this->deepLinkBaseUrl($clientType) . 'identity-restore?%s',
            http_build_query(array_filter([
                'token' => $emailToken,
                'target' => $target,
            ], static function ($value) {
                return $value !== null && $value !== '';
            }))
        );

        return ViewFacade::make('pages.auth.deep_link', [
            'type' => 'email_sign_in',
            'redirectUrl' => $redirectUrl,
            'exchangeToken' => $emailToken,
            ...$this->authPageContext($clientType, $implementationKey),
        ]);
    }

    /**
     * @param string $exchangeToken
     * @param string|null $clientType
     * @param string|null $implementationKey
     * @return View
     */
    public function emailConfirmationView(
        string $exchangeToken,
        ?string $clientType,
        ?string $implementationKey,
    ): View {
        $redirectUrl = sprintf(
            $this->deepLinkBaseUrl($clientType) . 'identity-confirmation?%s',
            http_build_query(['token' => $exchangeToken])
        );

        return ViewFacade::make('pages.auth.deep_link', [
            'type' => 'email_confirmation',
            'redirectUrl' => $redirectUrl,
            'exchangeToken' => $exchangeToken,
            ...$this->authPageContext($clientType, $implementationKey),
        ]);
    }

    /**
     * @param string $title
     * @param string $message
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public function authErrorResponse(string $title, string $message, int $status, array $headers = []): Response
    {
        return new Response(
            ViewFacade::make('pages.auth.error', compact('title', 'message')),
            $status,
            [...$headers, 'Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    /**
     * @param string|null $clientType
     * @param string|null $implementationKey
     * @return array
     */
    public function authPageContext(?string $clientType, ?string $implementationKey): array
    {
        return $this->appKeyForClientType($clientType) ? compact('clientType', 'implementationKey') : [];
    }
}
