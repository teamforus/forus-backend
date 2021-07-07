<?php

namespace App\Policies;

use App\Models\Employee;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
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
     * @return mixed
     */
    public function show(
        $identity_address
    ) {
        return !empty($identity_address);
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
     * @param Media $media
     * @return bool
     */
    public function destroy(
        $identity_address,
        Media $media
    ) {
        $identityCan = false;

        if ($media->type != 'implementation_banner') {
            return strcmp($media->identity_address, $identity_address) == 0;
        } elseif ($employee = Employee::getEmployee($identity_address)) {
            $identityCan = $employee->organization->identityCan(
                $identity_address, 'manage_implementation_cms'
            );
        }

        return $identityCan || strcmp($media->identity_address, $identity_address) == 0;
    }
}
