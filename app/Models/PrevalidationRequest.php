<?php

namespace App\Models;

use App\Events\PrevalidationRequests\PrevalidationRequestCreated;
use App\Events\PrevalidationRequests\PrevalidationRequestFailed;
use App\Events\PrevalidationRequests\PrevalidationRequestResubmitted;
use App\Events\PrevalidationRequests\PrevalidationRequestUpdated;
use App\Http\Requests\Api\Platform\Funds\Requests\StoreFundRequestCsvRequest;
use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;

/**
 *
 *
 * @property int $id
 * @property string $bsn
 * @property string $state
 * @property int $organization_id
 * @property int|null $employee_id
 * @property int $fund_id
 * @property int|null $prevalidation_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $failed_reason
 * @property-read EventLog|null $latest_failed_log
 * @property-read \Illuminate\Database\Eloquent\Collection|EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Models\Prevalidation|null $prevalidation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PrevalidationRequestRecord[] $records
 * @property-read int|null $records_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest whereBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest wherePrevalidationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PrevalidationRequest extends BaseModel
{
    use HasLogs;

    public const string EVENT_CREATED = 'created';
    public const string EVENT_UPDATED = 'updated';
    public const string EVENT_DELETED = 'deleted';
    public const string EVENT_RESUBMITTED = 'resubmitted';
    public const string EVENT_FAILED = 'failed';

    public const string STATE_PENDING = 'pending';
    public const string STATE_SUCCESS = 'success';
    public const string STATE_FAIL = 'fail';

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_SUCCESS,
        self::STATE_FAIL,
    ];

    public const string FAILED_REASON_INVALID_RECORDS = 'invalid_records';
    public const string FAILED_REASON_EMPTY_PREVALIDATIONS = 'empty_prevalidations';

    /**
     * @var array
     */
    protected $fillable = [
        'bsn', 'state', 'fund_id', 'organization_id', 'employee_id', 'prevalidation_id',
    ];

    /**
     * @return BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo
     */
    public function prevalidation(): BelongsTo
    {
        return $this->belongsTo(Prevalidation::class);
    }

    /**
     * @return HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(PrevalidationRequestRecord::class);
    }

    /**
     * @return MorphOne
     */
    public function latest_failed_log(): MorphOne
    {
        return $this->morphOne(EventLog::class, 'loggable')
            ->where('event', PrevalidationRequest::EVENT_FAILED)
            ->latestOfMany();
    }

    /**
     * @noinspection PhpUnused
     * @return string|null
     */
    public function getFailedReasonAttribute(): ?string
    {
        return Arr::get($this->latest_failed_log?->data ?? [], 'prevalidation_request_failed_reason');
    }

    /**
     * @param Fund $fund
     * @param Employee $employee
     * @param array $data
     * @return void
     */
    public static function makeFromArray(Fund $fund, Employee $employee, array $data): void
    {
        foreach ($data as $datum) {
            $item = PrevalidationRequest::create([
                'bsn' => $datum['bsn'],
                'state' => PrevalidationRequest::STATE_PENDING,
                'fund_id' => $fund->id,
                'employee_id' => $employee->id,
                'organization_id' => $fund->organization_id,
            ]);

            foreach ($datum as $record_type_key => $value) {
                $item->records()->create(compact('record_type_key', 'value'));
            }

            PrevalidationRequestCreated::dispatch($item);
        }
    }

    /**
     * @return $this
     */
    public function resubmit(): static
    {
        $prevState = $this->state;
        $this->update(['state' => PrevalidationRequest::STATE_PENDING]);

        Event::dispatch(new PrevalidationRequestResubmitted($this, $prevState));

        return $this;
    }

    /**
     * @return void
     */
    public function makePrevalidation(): void
    {
        $prevState = $this->state;
        $fundPrefills = IConnectPrefill::getBsnApiPrefills($this->fund, $this->bsn);

        if (is_array($fundPrefills['error'])) {
            $this->update(['state' => $this::STATE_FAIL]);
            Event::dispatch(new PrevalidationRequestFailed($this, Arr::get($fundPrefills, 'error.key')));

            return;
        }

        // prepare prefill records
        $data = Arr::mapWithKeys([
            ...Arr::get($fundPrefills, 'person', []),
            ...Arr::get($fundPrefills, 'partner', []),
            ...Arr::collapse(Arr::get($fundPrefills, 'children', [])),
            ...Arr::get($fundPrefills, 'children_groups_counts', []),
        ], fn (array $item) => [$item['record_type_key'] => $item['value']]);

        $data = [
            ...$data,
            ...$this->records->pluck('value', 'record_type_key')->toArray(),
        ];

        if (!$this->recordsIsValid($this->fund, $data)) {
            $this->update(['state' => $this::STATE_FAIL]);
            Event::dispatch(new PrevalidationRequestFailed($this, $this::FAILED_REASON_INVALID_RECORDS));

            return;
        }

        $prevalidations = Prevalidation::storePrevalidations(
            employee: $this->employee,
            fund: $this->fund,
            data: [$data],
            topUps: [],
            overwriteKeys: [],
        );

        if ($prevalidations->count() === 0) {
            $this->update(['state' => $this::STATE_FAIL]);
            Event::dispatch(new PrevalidationRequestFailed($this, $this::FAILED_REASON_EMPTY_PREVALIDATIONS));

            return;
        }

        $this->update([
            'state' => $this::STATE_SUCCESS,
            'prevalidation_id' => $prevalidations[0]->id,
        ]);

        Event::dispatch(new PrevalidationRequestUpdated($this, $prevState));
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @return bool
     */
    private function recordsIsValid(Fund $fund, array $data): bool
    {
        $criteriaByKey = $fund->criteria->pluck('id', 'record_type_key')->toArray();

        // get optional criteria to prefill them for validation
        $optionalCriteria = array_fill_keys(
            $fund->criteria
                ->where('optional', true)
                ->pluck('record_type_key')
                ->toArray(),
            null
        );

        $data = [
            ...$optionalCriteria,
            ...$data,
        ];

        $records = array_values(array_filter(array_map(function ($value, $record_type_key) use ($criteriaByKey) {
            return Arr::has($criteriaByKey, $record_type_key) ? [
                'value' => $value,
                'fund_criterion_id' => Arr::get($criteriaByKey, $record_type_key),
            ] : null;
        }, $data, array_keys($data))));

        $validator = Validator::make(
            [
                ...compact('records'),
                'criteria_groups' => $this->fund->criteria_groups->pluck('id', 'id')->toArray(),
            ],
            (new StoreFundRequestCsvRequest())->csvRules($fund, $records)
        );

        return $validator->passes();
    }
}
