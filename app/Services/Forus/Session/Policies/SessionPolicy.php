<?php

namespace App\Services\Forus\Session\Policies;

use App\Services\Forus\Session\Models\Session;
use Illuminate\Auth\Access\HandlesAuthorization;

class SessionPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function viewAny(
        $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Session $session
     * @return bool
     */
    public function show(
        $identity_address, Session $session
    ) {
        return strcmp($session->identity_address, $identity_address) === 0;
    }

    /**
     * @param $identity_address
     * @param Session $session
     * @return bool
     */
    public function terminate(
        $identity_address, Session $session
    ) {
        return strcmp($session->identity_address, $identity_address) === 0;
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function terminateAll(
        $identity_address
    ) {
        return !empty($identity_address);
    }
}
