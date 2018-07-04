<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;

/**
 * Class RecordTypeController
 * @package App\Http\Controllers\Api\Identity
 */
class RecordTypeController extends Controller
{
    private $recordRepo;

    /**
     * RecordTypeController constructor.
     */
    public function __construct() {
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index()
    {
        return collect($this->recordRepo->getRecordTypes())->map(function($type) {
            return collect($type)->only('key', 'name', 'type');
        });
    }
}
