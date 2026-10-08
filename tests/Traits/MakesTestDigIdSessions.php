<?php

namespace Tests\Traits;

use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Objects\DigIdSessionData;
use Throwable;

trait MakesTestDigIdSessions
{
    /**
     * @param DigIdSessionData $data
     * @param string $bsn
     * @param string $completionCode
     * @throws Throwable
     * @return DigIdSession
     */
    protected function makeAuthorizedDigIdSession(
        DigIdSessionData $data,
        string $bsn,
        string $completionCode,
    ): DigIdSession {
        $session = DigIdSession::createSession($data);

        $session->update([
            'state' => DigIdSession::STATE_AUTHORIZED,
            'digid_uid' => $bsn,
            'meta' => [
                ...$session->meta,
                'completion_code_hash' => hash('sha256', $completionCode),
                'completion_consumed' => false,
            ],
        ]);

        return $session;
    }
}
