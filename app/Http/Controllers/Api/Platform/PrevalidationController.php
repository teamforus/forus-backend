<?php

namespace App\Http\Controllers\Api\Platform;

use Illuminate\Support\Str;
use App\Http\Requests\Api\Platform\UploadPrevalidationsRequest;
use App\Http\Resources\PrevalidationResource;
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

        $data = $request->input('data');

        $data = collect($data)->map(function($record) {
            return collect($record)->map(function($value, $key) {
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
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request) {
        $this->authorize('index', Prevalidation::class);

        return PrevalidationResource::collection(
            Prevalidation::search(
                auth()->user()->getAuthIdentifier(),
                $request->input('q', false)
            )->with('records.record_type')->paginate(
                $request->has('per_page') ? $request->input('per_page') : null
            )
        );
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
                $validationRequest['uuid']
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
        return Str::lower($request->input(auth()->id())).'|'.$request->ip();
    }
}
