<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\ValidatorResource;
use App\Models\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        $bsnValidations = collect($this->recordRepo->recordsList(
            auth()->user()->getAuthIdentifier()
        ))->filter(function($record) {
            return !empty($record['validations']) && $record['key'] == 'bsn';
        })->pluck('validations.*.identity_address')->flatten();

        $bsnValidations = $bsnValidations->unique();

        return ValidatorResource::collection(Validator::getModel()->whereIn(
            'identity_address', $bsnValidations
        )->get());
    }
}
