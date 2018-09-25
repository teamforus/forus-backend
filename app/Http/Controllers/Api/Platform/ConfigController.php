<?php

namespace App\Http\Controllers\Api\Platform;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ConfigController extends Controller
{
    public function getConfig($config) {
        return $config;
    }
}
