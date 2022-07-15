<?php

namespace App\Services\Forus\Session;

use App\Http\Requests\BaseFormRequest;
use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequest;

class SessionService
{
    /**
     * @param string $client_type
     * @param string|null $identity_proxy_id
     * @param string|null $identity_address
     * @return string
     */
    public static function makeUid(
        string $client_type,
        string $identity_proxy_id = null,
        string $identity_address = null
    ): string {
        $dashboards = config('forus.clients.dashboards', []);
        $isDashboard = in_array($client_type, $dashboards);

        $data = implode('_', [
            is_null($identity_address) ? '-' : $identity_address,
            is_null($identity_proxy_id) ? '-' : $identity_proxy_id,
            $isDashboard ? 'dashboards' : $client_type,
        ]);

        return sha1($data) . sha1($identity_proxy_id);
    }

    /**
     * @param string $ip
     * @param string $client_type
     * @param string|null $client_version
     * @param string|null $identity_proxy_id
     * @param string|null $identity_address
     * @return Session|null
     */
    public function makeOrUpdateSession(
        string $ip,
        string $client_type,
        string $client_version = null,
        string $identity_proxy_id = null,
        string $identity_address = null
    ): ?Session {
        $uid = self::makeUid($client_type, $identity_proxy_id, $identity_address);
        $session = self::makeOrGetSession($uid, $identity_address, $identity_proxy_id);

        if (!$session) {
            return null;
        }

        $metasData = array_merge(compact('ip', 'client_type', 'client_version'), [
            'id' => request()->ip(),
            'method' => request()->method(),
            'endpoint' => request()->getRequestUri(),
            'user_agent' => request()->userAgent(),
        ]);

        $session->update([
            'last_activity_at' => now(),
        ]);

        /** @var SessionRequest $sessionRequest */
        $session->requests()->create($metasData);

        return $session;
    }

    /**
     * @return Session|null
     */
    public static function currentSession(): ?Session
    {
        $request = BaseFormRequest::createFrom(request());
        $authAddress = $request->identityProxy()?->identity_address;
        $authProxyId = $request->identityProxy()?->id;

        return self::getSession(
            self::makeUid($request->client_type(), $authProxyId, $authAddress),
            $authAddress,
            $authProxyId
        );
    }

    /**
     * @param string $uid
     * @param string|null $identity_address
     * @param int|null $identity_proxy_id
     * @return Session|null
     */
    public static function getSession(
        string $uid,
        ?string $identity_address = null,
        ?int $identity_proxy_id = null
    ): ?Session {
        return Session::where([
            'uid' => $uid,
            'identity_address' => $identity_address,
            'identity_proxy_id' => $identity_proxy_id,
        ])->first();
    }

    /**
     * @param string $uid
     * @param string|null $identity_address
     * @param int|null $identity_proxy_id
     * @return Session|null
     */
    public static function makeOrGetSession(
        string $uid,
        ?string $identity_address = null,
        ?int $identity_proxy_id = null,
    ): ?Session {
        Session::insertOrIgnore([
            'uid' => $uid,
            'identity_address' => $identity_address,
            'identity_proxy_id' => $identity_proxy_id,
        ]);

        return self::getSession($uid, $identity_address, $identity_proxy_id);
    }
}