<?php
namespace App\Services\DigIdService\Repositories\Interfaces;

use App\Services\DigIdService\DigIdException;

interface IDigIdRepo {
    /**
     * @param string $app_url
     * @param array $extraParams
     * @return array|bool
     * @throws DigIdException
     */
    public function makeAuthRequest(
        $app_url = "", array $extraParams = []);

    /**
     * @param string $rid
     * @param string $aselect_server
     * @param string $aselect_credentials
     * @return mixed
     * @throws DigIdException
     */
    public function getBsnFromResponse(
        string $rid,
        string $aselect_server,
        string $aselect_credentials
    );
}
