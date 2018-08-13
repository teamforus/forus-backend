<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\ValidatorResource;
use App\Models\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ValidatorsController extends Controller
{
    protected $identityRepo;
    protected $recordRepo;

    public function __construct() {
        $this->identityRepo = app()->make('forus.services.identity');
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $identity = $request->get('identity');

        $bsnValidations = collect($this->recordRepo->recordsList(
            $identity
        ))->filter(function($record) {
            return !empty($record['validations']) && $record['key'] == 'bsn';
        })->pluck('validations.*.identity_address')->flatten();

        $bsnValidations = $bsnValidations->unique();

        return ValidatorResource::collection(Validator::getModel()->groupBy(
            'identity_address'
        )->whereIn(
            'identity_address', $bsnValidations
        )->get());
    }
}
