<?php

namespace App\Policies;

use App\Models\ValidatorRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class ValidatorRequestPolicy
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
    public function index($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param ValidatorRequest $validatorRequest
     * @return bool
     */
    public function show(
        $identity_address,
        ValidatorRequest $validatorRequest
    ) {
        return $validatorRequest->identity_address == $identity_address ||
            $validatorRequest->validator->identity_address == $identity_address;
    }

    /**
     * @param $identity_address
     * @return bool
     */
    public function request($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @return bool
     */
    public function update() {
        return false;
    }

    /**
     * @param $identity_address
     * @param ValidatorRequest $validatorRequest
     * @return bool
     */
    public function validate(
        $identity_address,
        ValidatorRequest $validatorRequest
    ) {
        return (strcmp(
                $validatorRequest->validator->identity_address,
                $identity_address
            ) == 0) && $validatorRequest->state == 'pending';
    }

    /**
     * @param $identity_address
     * @param ValidatorRequest $validatorRequest
     * @return bool
     */
    public function destroy(
        $identity_address,
        ValidatorRequest $validatorRequest
    ) {
        return strcmp(
                $validatorRequest->identity_address,
                $identity_address
            ) == 0;
    }
}
