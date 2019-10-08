<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\ValidatorResource;
use App\Models\Validator;
use App\Http\Controllers\Controller;

/**
 * Class ValidatorsController
 * @package App\Http\Controllers\Api\Platform
 */
class ValidatorsController extends Controller
{
    protected $recordRepo;

    public function __construct() {
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index() {
        $this->authorize(Validator::class, 'index');

        $uidValidations = collect($this->recordRepo->recordsList(
            auth_address()
        ))->filter(function($record) {
            return !empty($record['validations']) && $record['key'] == 'uid';
        })->pluck('validations.*.identity_address')->flatten();

        return ValidatorResource::collection(Validator::query()->whereIn(
            'identity_address', $uidValidations->unique()
        )->get());
    }
}
