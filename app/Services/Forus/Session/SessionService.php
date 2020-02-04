<?php

namespace App\Services\Forus\Session;

use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequest;
use Illuminate\Database\Eloquent\Model;

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
    ) {
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
     * @return Session|bool|Model
     */
    public function makeOrUpdateSession(
        string $ip,
        string $client_type,
        string $client_version = null,
        string $identity_proxy_id = null,
        string $identity_address = null
    ) {
        $uid = self::makeUid($client_type, $identity_proxy_id, $identity_address);

        if (!$session = self::getSession(
            $uid, $identity_address, $identity_proxy_id
        )) {
            if (!$session = self::makeSession(
                $uid, $identity_address, $identity_proxy_id
            )) {
                return false;
            }
        }

        $metasData = collect(compact(
            'ip', 'client_type', 'client_version'
        ))->merge([
            'id' => request()->ip(),
            'method' => request()->method(),
            'endpoint' => request()->getRequestUri(),
            'user_agent' => request()->userAgent(),
        ]);

        $session->update([
            'last_activity_at' => now()
        ]);

        /** @var SessionRequest $sessionRequest */
        $session->requests()->create($metasData->toArray());

        return $session;
    }

    /**
     * @return Session|\Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function currentSession() {
        return self::getSession(self::makeUid(
            client_type(),
            auth_proxy_id(),
            auth_address()
        ), auth_address(), auth_proxy_id());
    }

    /**
     * @param string $uid
     * @param null $identity_address
     * @param null $identity_proxy_id
     * @return Session|\Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function getSession(
        string $uid,
        $identity_address = null,
        $identity_proxy_id = null
    ) {
        return Session::where(compact(
            'uid', 'identity_address', 'identity_proxy_id'
        ))->first();
    }

    /**
     * @param string $uid
     * @param null $identity_address
     * @param null $identity_proxy_id
     * @return Session|bool|Model
     */
    public static function makeSession(
        string $uid,
        $identity_address = null,
        $identity_proxy_id = null
    ) {
        Session::insertOrIgnore(compact(
            'uid', 'identity_address', 'identity_proxy_id'
        ));

        return Session::where(compact(
            'uid', 'identity_address', 'identity_proxy_id'
        ))->first();
    }
}