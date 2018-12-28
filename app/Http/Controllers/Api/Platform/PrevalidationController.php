<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SearchPrevalidationsRequest;
use App\Http\Requests\Api\Platform\UploadPrevalidationsRequest;
use App\Http\Resources\PrevalidationResource;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use App\Http\Controllers\Controller;

class PrevalidationController extends Controller
{
    private $recordRepo;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * @param UploadPrevalidationsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        UploadPrevalidationsRequest $request
    ) {
        $this->authorize('store', Prevalidation::class);

        $data = $request->input('data');

        /** @var Fund $fund */
        $fund = $request->input('fund_id', false);
        $primaryKeyValues = collect();

        if ($fund) {
            $fund = Fund::query()->find($fund);
            $fundPrevalidationPrimaryKey = resolve('forus.services.record')->getTypeIdByKey(
                $fund->fund_config->csv_primary_key
            );

            $existingPrevalidations = Prevalidation::query()->where([
                'identity_address' => auth()->id(),
                'fund_id' => $fund->id
            ])->pluck('id');

            $primaryKeyValues = PrevalidationRecord::query()->whereIn(
                'prevalidation_id', $existingPrevalidations
            )->where([
                'record_type_id' => $fundPrevalidationPrimaryKey,
            ])->pluck('value');
        }


        $data = collect($data)->map(function($record) use (
            $primaryKeyValues, $fund
        ) {
            $record = collect($record);

            if ($fund) {
                if ($primaryKeyValues->search(
                    $record[$fund->fund_config->csv_primary_key]
                    ) !== false) {
                    return [];
                }
            }

            return $record->map(function($value, $key) {
                $record_type_id = app()->make(
                    'forus.services.record'
                )->getTypeIdByKey($key);

                if (!$record_type_id || $key == 'primary_email') {
                    return false;
                }

                if (is_null($value)) {
                    $value = '';
                }

                return compact('record_type_id', 'value');
            })->filter(function($value) {
                return !!$value;
            })->values();
        })->filter(function($records) {
            return collect($records)->count();
        })->map(function($records) use ($request) {
            do {
                $uid = app()->make('token_generator')->generate(4, 2);
            } while(Prevalidation::getModel()->where(
                'uid', $uid
            )->count() > 0);

            $prevalidation = Prevalidation::create([
                'uid' => $uid,
                'state' => 'pending',
                'fund_id' => $request->input('fund_id', null),
                'identity_address' => auth()->user()->getAuthIdentifier()
            ]);

            foreach ($records as $record) {
                $prevalidation->records()->create($record);
            }

            $prevalidation->load('records');

            return $prevalidation;
        });

        return PrevalidationResource::collection($data);
    }

    /**
     * @param SearchPrevalidationsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        SearchPrevalidationsRequest $request
    ) {
        $this->authorize('index', Prevalidation::class);

        return PrevalidationResource::collection(Prevalidation::search(
            auth()->user()->getAuthIdentifier(),
            $request->input('q', null),
            $request->input('fund_id', null),
            $request->input('state', null),
            $request->input('from', null),
            $request->input('to', null)
        )->with('records.record_type')->paginate(
            $request->get('per_page', null)
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Prevalidation $prevalidation
     * @return PrevalidationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Prevalidation $prevalidation)
    {
        $this->authorize('show', $prevalidation);

        return new PrevalidationResource($prevalidation);
    }

    /**
     * Redeem prevalidation.
     *
     * @param Prevalidation $prevalidation
     * @return PrevalidationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function redeem(
        Prevalidation $prevalidation
    ) {
        $this->authorize('redeem', $prevalidation);

        foreach($prevalidation->records as $record) {
            /** @var $record PrevalidationRecord */
            $record = $this->recordRepo->recordCreate(
                auth()->user()->getAuthIdentifier(),
                $record->record_type->key,
                $record->value
            );

            $validationRequest = $this->recordRepo->makeValidationRequest(
                auth()->user()->getAuthIdentifier(),
                $record['id']
            );

            $this->recordRepo->approveValidationRequest(
                $prevalidation->identity_address,
                $validationRequest['uuid']
            );
        }

        $prevalidation->update([
            'state' => 'used'
        ]);

        return new PrevalidationResource($prevalidation);
    }
}
