<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;

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
