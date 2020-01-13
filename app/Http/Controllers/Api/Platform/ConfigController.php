<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;

/**
 * Class ConfigController
 * @package App\Http\Controllers\Api\Platform
 */
class ConfigController extends Controller
{
    /**
     * @param $config
     * @return mixed
     */
    public function getConfig($config) {
        return $config;
    }
}
