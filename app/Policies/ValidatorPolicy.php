<?php

namespace App\Policies;

use App\Models\Validator;
use Illuminate\Auth\Access\HandlesAuthorization;

class ValidatorPolicy
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
     * @return mixed
     */
    public function show($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function store($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Validator $Validator
     * @return bool
     */
    public function update($identity_address, Validator $Validator) {
        return strcmp(
            $Validator->organization->identity_address,
            $identity_address
            ) == 0;
    }

    /**
     * @param $identity_address
     * @param Validator $Validator
     * @return bool
     */
    public function destroy($identity_address, Validator $Validator) {
        return strcmp(
            $Validator->organization->identity_address,
            $identity_address
            ) == 0;
    }
}
