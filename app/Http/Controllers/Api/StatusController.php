<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Class StatusController
 * @package App\Http\Controllers\Api
 */
class StatusController extends Controller
{
    /**
     * Get api availability state
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function getStatus() {
        try {
            DB::connection()->getPdo();
            return response(null, 200);
        } catch (\Exception $e) {
            return response(null, 503);
        }
    }
}
