<?php

namespace App\Http\Controllers\Api\Platform;

use App\Exports\PrevalidationsExport;
use App\Http\Requests\Api\Platform\SearchPrevalidationsRequest;
use Illuminate\Support\Str;
use App\Http\Requests\Api\Platform\UploadPrevalidationsRequest;
use App\Http\Resources\PrevalidationResource;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PrevalidationController extends Controller
{
    use ThrottlesLogins;

    private $recordRepo;
    private $maxAttempts = 3;
    private $decayMinutes = 180;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = app()->make('forus.services.record');
        $this->maxAttempts = env('ACTIVATION_CODE_ATTEMPTS', $this->maxAttempts);
        $this->decayMinutes = env('ACTIVATION_CODE_DECAY', $this->decayMinutes);
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

        $recordRepo = resolve('forus.services.record');

        $fund = Fund::find($request->input('fund_id'));
        $data = $request->input('data');

        $fundPrevalidationPrimaryKey = $recordRepo->getTypeIdByKey(
            $fund->fund_config->csv_primary_key
        );

        $existingPrevalidations = Prevalidation::where([
            'identity_address' => auth()->id(),
            'fund_id' => $fund->id
        ])->pluck('id');

        $primaryKeyValues = PrevalidationRecord::whereIn(
            'prevalidation_id', $existingPrevalidations
        )->where([
            'record_type_id' => $fundPrevalidationPrimaryKey,
        ])->pluck('value');

        $data = collect($data)->map(function($record) use (
            $primaryKeyValues, $fund
        ) {
            $record = collect($record);

            if ($primaryKeyValues->search(
                $record[$fund->fund_config->csv_primary_key]) !== false) {
                return [];
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
        })->map(function($records) use ($request, $fund) {
            do {
                $uid = app()->make('token_generator')->generate(4, 2);
            } while(Prevalidation::query()->where(
                'uid', $uid
            )->count() > 0);

            $prevalidation = Prevalidation::create([
                'uid' => $uid,
                'state' => 'pending',
                'organization_id' => $fund->organization_id,
                'fund_id' => $fund->id,
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
            $request
        )->with('records.record_type')->paginate());
    }

    /**
     * @param SearchPrevalidationsRequest $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(
        SearchPrevalidationsRequest $request
    ) {
        $this->authorize('index', Prevalidation::class);

        return resolve('excel')->download(
            new PrevalidationsExport($request),
            date('Y-m-d H:i:s') . '.xls'
        );
    }

    /**
     * @param Request $request
     * @param Prevalidation $prevalidation
     * @return array
     */
    public function showFundId(
        Request $request,
        Prevalidation $prevalidation
    ) {
        if ($this->hasTooManyLoginAttempts($request)) {
            abort(429, 'To many attempts.');
        }

        $this->incrementLoginAttempts($request);

        return [
            'data' => $prevalidation->only('fund_id')
        ];
    }

    /**
     * Redeem prevalidation.
     *
     * @param Request $request
     * @param Prevalidation|null $prevalidation
     * @return PrevalidationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function redeem(
        Request $request,
        Prevalidation $prevalidation = null
    ) {
        if ($this->hasTooManyLoginAttempts($request)) {
            abort(429, 'To many attempts.');
        }

        $this->incrementLoginAttempts($request);
        $this->authorize('redeem', $prevalidation);
        $this->clearLoginAttempts($request);

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
                $validationRequest['uuid'],
                $prevalidation->organization_id
            );
        }

        $prevalidation->update([
            'state' => 'used'
        ]);

        return new PrevalidationResource($prevalidation);
    }

    /**
     * Get the throttle key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        return Str::lower($request->ip());
    }
}
