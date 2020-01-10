<?php

namespace App\Services\Forus\Session;

use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Models\SessionRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class SessionService
{
    const RETRIES = 10;
    const RETRY_THRESHOLD = .25;

    /**
     * @param string $ip
     * @param string $client_type
     * @param string|null $client_version
     * @param string|null $identity_proxy_id
     * @param string|null $identity_address
     * @return Session|bool|Model
     */
    public function updateSession(
        string $ip,
        string $client_type,
        string $client_version = null,
        string $identity_proxy_id = null,
        string $identity_address = null
    ) {
        $hash = sha1(implode('_', [
            $identity_address ?: '-',
            $identity_proxy_id ?: '-',
            $client_type,
            $client_version ?: '-',
        ]));

        if (!$session = self::getSession($hash)) {
            if (!$session = self::makeSession($hash)) {
                return false;
            }
        }

        $metasData = collect(compact(
            'ip', 'client_type', 'client_version', 'identity_address',
            'identity_proxy_id'
        ));

        $metasData = $metasData->map(function($value, $key) {
            return compact('key', 'value');
        })->toArray();

        /** @var SessionRequest $sessionRequest */
        $sessionRequest = $session->requests()->create();
        $sessionRequest->metas()->createMany($metasData);

        return $session;
    }

    /**
     * @param string $hash
     * @return Session|Model
     */
    public static function getSession(string $hash) {
        return Session::where(compact('hash'))->first();
    }

    /**
     * @param string $hash
     * @param int $tries
     * @param float $retry_threshold
     * @return Session|bool|Model
     */
    public static function makeSession(
        string $hash,
        $tries = self::RETRIES,
        $retry_threshold = self::RETRY_THRESHOLD
    ) {
        if ($tries == 0) {
            return FALSE;
        }

        try {
            return Session::firstOrCreate(compact('hash'));
        } catch (QueryException $exception) {
            usleep(100000 * $retry_threshold);
            return self::makeSession($hash, $tries - 1);
        }
    }
}