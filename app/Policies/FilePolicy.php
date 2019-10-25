<?php

namespace App\Policies;

use App\Models\FundRequestRecord;
use App\Services\FileService\Models\File;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
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
    public function index(
        $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param File $file
     * @return bool
     */
    public function show(
        $identity_address, File $file
    ) {
        if (empty($identity_address)) {
            return false;
        }

        // is file owner/creator
        if (strcmp($file->identity_address, $identity_address) === 0) {
            return true;
        }

        // is fund request proof
        if (strcmp($file->type, 'fund_request_record_proof')  === 0) {
            // is fund validator
            return ($file->fileable instanceof FundRequestRecord) && in_array(
                $identity_address,
                $file->fileable->fund_request->fund->validatorEmployees());
        }

        return false;
    }

    /**
     * @param $identity_address
     * @param File $file
     * @return bool
     */
    public function download(
        $identity_address, File $file
    ) {
        return $this->show($identity_address, $file);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function store(
        $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param File $file
     * @return bool
     */
    public function destroy(
        $identity_address,
        File $file
    ) {
        return (strcmp($file->identity_address, $identity_address) === 0);
    }
}
