<?php

namespace App\Models;

use App\Events\FundRequests\FundRequestAssigned;
use App\Events\FundRequests\FundRequestResigned;
use App\Events\FundRequests\FundRequestResolved;
use App\Helpers\Validation;
use App\Models\Traits\HasNotes;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Rules\Base\IbanRule;
use App\Searches\FundRequestSearch;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;

/**
 * App\Models\FundRequest
 *
 * @property int $id
 * @property int $fund_id
 * @property string $identity_address
 * @property int|null $employee_id
 * @property string|null $contact_information
 * @property string $note
 * @property string $disregard_note
 * @property bool $disregard_notify
 * @property string $state
 * @property string|null $amount
 * @property int|null $fund_amount_preset_id
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Models\FundRequestClarification[] $clarifications
 * @property-read int|null $clarifications_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\FundAmountPreset|null $fund_amount_preset
 * @property-read int|null $lead_time_days
 * @property-read string $lead_time_locale
 * @property-read string $state_locale
 * @property-read \App\Models\Identity|null $identity
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|\App\Models\Note[] $notes
 * @property-read int|null $notes_count
 * @property-read Collection|\App\Models\FundRequestRecord[] $records
 * @property-read int|null $records_count
 * @property-read Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static Builder|FundRequest newModelQuery()
 * @method static Builder|FundRequest newQuery()
 * @method static Builder|FundRequest query()
 * @method static Builder|FundRequest whereAmount($value)
 * @method static Builder|FundRequest whereContactInformation($value)
 * @method static Builder|FundRequest whereCreatedAt($value)
 * @method static Builder|FundRequest whereDisregardNote($value)
 * @method static Builder|FundRequest whereDisregardNotify($value)
 * @method static Builder|FundRequest whereEmployeeId($value)
 * @method static Builder|FundRequest whereFundAmountPresetId($value)
 * @method static Builder|FundRequest whereFundId($value)
 * @method static Builder|FundRequest whereId($value)
 * @method static Builder|FundRequest whereIdentityAddress($value)
 * @method static Builder|FundRequest whereNote($value)
 * @method static Builder|FundRequest whereResolvedAt($value)
 * @method static Builder|FundRequest whereState($value)
 * @method static Builder|FundRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequest extends BaseModel
{
    use HasLogs, HasNotes;

    public const EVENT_CREATED = 'created';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_DECLINED = 'declined';
    public const EVENT_RESOLVED = 'resolved';
    public const EVENT_ASSIGNED = 'assigned';
    public const EVENT_RESIGNED = 'resigned';
    public const EVENT_DISREGARDED = 'disregarded';

    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';
    public const STATE_DISREGARDED = 'disregarded';

    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_APPROVED,
        self::EVENT_DECLINED,
        self::EVENT_RESOLVED,
        self::EVENT_ASSIGNED,
        self::EVENT_RESIGNED,
    ];

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_DISREGARDED,
    ];

    public const STATES_RESOLVED = [
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_DISREGARDED,
    ];

    public const STATES_ARCHIVED = [
        self::STATE_DECLINED,
    ];

    protected $fillable = [
        'fund_id', 'identity_address', 'employee_id', 'note', 'state', 'resolved_at',
        'disregard_note', 'disregard_notify', 'identity_address',
        'contact_information',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'disregard_notify' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return int|null
     * @noinspection PhpUnused
     */
    public function getLeadTimeDaysAttribute(): ?int
    {
        return ($this->resolved_at ?: now())->diffInDays($this->created_at);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getLeadTimeLocaleAttribute(): string
    {
        return ($this->resolved_at ?: now())->longAbsoluteDiffForHumans($this->created_at);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return [
            self::STATE_PENDING => 'Wachten',
            self::STATE_APPROVED => 'Geaccepteerd',
            self::STATE_DECLINED => 'Geweigerd',
            self::STATE_DISREGARDED => 'Niet beoordeeld',
        ][$this->state] ?? '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_amount_preset(): BelongsTo
    {
        return $this->belongsTo(FundAmountPreset::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(FundRequestRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function clarifications(): HasManyThrough
    {
        return $this->hasManyThrough(
            FundRequestClarification::class,
            FundRequestRecord::class
        );
    }

    /**
     * @param string|null $note
     * @return FundRequest
     * @throws \Exception
     */
    public function decline(?string $note = null): self
    {
        $this->update([
            'note' => $note ?: '',
            'state' => FundRequest::STATE_DECLINED,
            'resolved_at' => now(),
        ]);

        FundRequestResolved::dispatch($this);

        return $this;
    }

    /**
     * @return FundRequest
     */
    public function approve(): self
    {
        $this->update([
            'state' => FundRequest::STATE_APPROVED,
            'resolved_at' => now(),
        ]);

        $this->records()->get()->each(function (FundRequestRecord $record) {
            $record->makeValidation();
        });

        FundRequestResolved::dispatch($this);

        return $this;
    }

    /**
     * Set all fund request pending records assigned to given employee as disregarded
     *
     * @param string|null $note
     * @param bool $notify
     * @return FundRequest
     */
    public function disregard(?string $note = null, bool $notify = false): self
    {
        $this->update([
            'disregard_note' => $note ?: '',
            'disregard_notify' => $notify,
            'state' => FundRequest::STATE_DISREGARDED,
        ]);

        FundRequestResolved::dispatch($this);

        return $this;
    }

    /**
     * @return $this
     */
    public function disregardUndo(): self
    {
        $this->setState(FundRequest::STATE_PENDING);

        return $this;
    }

    /**
     * Assign all available pending fund request records to given employee
     * @param Employee $employee
     * @param Employee|null $supervisorEmployee
     * @return $this
     */
    public function assignEmployee(Employee $employee, ?Employee $supervisorEmployee = null): self
    {
        if ($this->employee) {
            $this->resignEmployee($employee, $supervisorEmployee);
        }

        $this->update([
            'employee_id' => $employee->id,
        ]);

        FundRequestAssigned::dispatch($this, $employee, $supervisorEmployee);

        return $this;
    }

    /**
     * Remove all assigned fund request records from employee
     * @param Employee $employee
     * @param Employee|null $supervisorEmployee
     * @return $this
     */
    public function resignEmployee(Employee $employee, ?Employee $supervisorEmployee = null): self
    {
        $this->records()->where([
            'record_type_key' => 'partner_bsn'
        ])->forceDelete();

        $this->update([
            'employee_id' => null,
        ]);

        FundRequestResigned::dispatch($this, $employee, $supervisorEmployee);

        return $this;
    }

    /**
     * Prepare fund requests for exporting
     *
     * @param Builder $builder
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(Builder $builder): mixed
    {
        $fundRequests = (clone $builder)->with([
            'identity.record_bsn',
            'records',
            'fund',
        ])->get();

        $recordKeyList = FundRequestRecord::query()
            ->whereIn('fund_request_id', (clone $builder)->select('id'))
            ->pluck('record_type_key');

        return $fundRequests->map(static function(FundRequest $request) use ($recordKeyList) {
            $records = $recordKeyList->reduce(fn ($records, $key) => [
                ...$records, $key => $request->records->firstWhere('record_type_key', $key),
            ], []);

            return array_merge([
                trans("export.fund_requests.bsn") => $request->identity?->record_bsn?->value ?: '-',
                trans("export.fund_requests.fund_name") => $request->fund->name,
                trans("export.fund_requests.status") => trans("export.fund_requests.state-values.$request->state"),
                trans("export.fund_requests.validator") => $request->employee?->identity?->email ?: '-',
                trans("export.fund_requests.created_at") => $request->created_at,
                trans("export.fund_requests.resolved_at") => $request->resolved_at,
                trans("export.fund_requests.lead_time_days") => (string) $request->lead_time_days,
                trans("export.fund_requests.lead_time_locale") => $request->lead_time_locale,
            ], array_map(fn(?FundRequestRecord $record = null) => $record?->value ?: '-', $records));
        })->values();
    }

    /**
     * Export fund requests
     * @param IndexFundRequestsRequest $request
     * @param Employee $employee
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function exportSponsor(IndexFundRequestsRequest $request, Employee $employee): mixed
    {
        $search = (new FundRequestSearch($request->only([
            'q', 'state', 'employee_id', 'from', 'to', 'order_by', 'order_dir', 'assigned',
        ])))->setEmployee($employee);

        return self::exportTransform($search->query());
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isDisregarded(): bool
    {
        return $this->state === self::STATE_DISREGARDED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isApproved(): bool
    {
        return $this->state === self::STATE_APPROVED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isDeclined(): bool
    {
        return $this->state === self::STATE_DECLINED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isResolved(): bool
    {
        return in_array($this->state, self::STATES_RESOLVED);
    }

    /**
     * @return array
     */
    private function getTrustedAndPendingRecordValues(): array
    {
        $recordTypes = array_unique([
            ...$this->fund->fund_formula_products->pluck('record_type_key_multiplier')->filter(),
            ...$this->fund->fund_formulas->pluck('record_type_key')->filter(),
        ]);

        $trustedValues = $this->fund->getTrustedRecordOfTypes(
            $this->identity_address,
            $recordTypes,
        );

        return  [
            ...$trustedValues,
            ...$this->records->pluck('value', 'record_type_key')->toArray(),
        ];
    }

    /**
     * @return array
     */
    public function formulaPreview(): array
    {
        $values = $this->getTrustedAndPendingRecordValues();

        $products = $this->fund->fund_formula_products->sortByDesc('product_id')->map(fn ($formula) => [
            'record' => $formula->record_type ? $formula->record_type->name : 'Product tegoed',
            'type' => $formula->record_type_key_multiplier ? 'Multiply' : 'Vastgesteld',
            'value' => $formula->product->name,
            'count' => $formula->record_type_key_multiplier ? Arr::get($values, $formula->record_type_key_multiplier) : 1,
            'total' => $formula->product->name,
        ]);

        $formula = $this->fund->fund_formulas->map(fn ($formula) => [
            'record' => $formula->record_type ? $formula->record_type->name : 'Vastbedrag',
            'type' => $formula->type_locale,
            'value' => $formula->amount_locale,
            'count' => $formula->record_type_key ? Arr::get($values, $formula->record_type_key) : 1,
            'total' => currency_format_locale($formula->amount),
            'amount' => currency_format($formula->amount),
        ]);

        return [
            'total_products' => $products->sum('count'),
            'total_amount' => currency_format_locale($formula->sum('amount')),
            'products' => $products->toArray(),
            'items' => $formula,
        ];
    }

    /**
     * @return FundAmountPreset|string|null
     */
    public function getPaymentAmount(): FundAmountPreset|string|null
    {
        return $this->fund_amount_preset ?: $this->amount;
    }

    /**
     * @return string
     */
    public function getIban(): string
    {
        return $this->fund->getTrustedRecordOfType(
            $this->identity_address,
            $this->fund->fund_config->iban_record_key,
        )?->value ?: '';
    }

    /**
     * @return string
     */
    public function getIbanName(): string
    {
        return $this->fund->getTrustedRecordOfType(
            $this->identity_address,
            $this->fund->fund_config->iban_name_record_key,
        )?->value ?: '';
    }

    /**
     * @return string|null
     */
    public function getResolvingError(): ?string
    {
        if ($this->fund->fund_config->isPayoutOutcome()) {
            $values = $this->getTrustedAndPendingRecordValues();

            if (!Arr::get($values, $this->fund?->fund_config->iban_record_key) ||
                !Arr::get($values, $this->fund?->fund_config->iban_name_record_key)) {
                return "invalid_iban_record_values";
            }

            if (Validation::check(Arr::get($values, $this->fund?->fund_config->iban_record_key), [
                'required', new IbanRule(),
            ])->fails()) {
                return "invalid_iban_format";
            }

            return $this->fund->getResolvingError();
        }

        return null;
    }
}
