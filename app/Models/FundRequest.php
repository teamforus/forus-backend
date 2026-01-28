<?php

namespace App\Models;

use App\Events\FundRequests\FundRequestAssigned;
use App\Events\FundRequests\FundRequestPhysicalCardRequestEvent;
use App\Events\FundRequests\FundRequestResigned;
use App\Events\FundRequests\FundRequestResolved;
use App\Helpers\Validation;
use App\Models\Traits\HasNotes;
use App\Rules\Base\IbanRule;
use App\Services\EventLogService\Traits\HasLogs;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * App\Models\FundRequest.
 *
 * @property int $id
 * @property int|null $identity_id
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
 * @property-read Collection|\App\Models\PhysicalCardRequest[] $physical_card_requests
 * @property-read int|null $physical_card_requests_count
 * @property-read Collection|\App\Models\FundRequestRecord[] $records
 * @property-read int|null $records_count
 * @property-read Collection|\App\Models\Voucher[] $vouchers
 * @property-read int|null $vouchers_count
 * @method static Builder<static>|FundRequest newModelQuery()
 * @method static Builder<static>|FundRequest newQuery()
 * @method static Builder<static>|FundRequest query()
 * @method static Builder<static>|FundRequest whereAmount($value)
 * @method static Builder<static>|FundRequest whereContactInformation($value)
 * @method static Builder<static>|FundRequest whereCreatedAt($value)
 * @method static Builder<static>|FundRequest whereDisregardNote($value)
 * @method static Builder<static>|FundRequest whereDisregardNotify($value)
 * @method static Builder<static>|FundRequest whereEmployeeId($value)
 * @method static Builder<static>|FundRequest whereFundAmountPresetId($value)
 * @method static Builder<static>|FundRequest whereFundId($value)
 * @method static Builder<static>|FundRequest whereId($value)
 * @method static Builder<static>|FundRequest whereIdentityAddress($value)
 * @method static Builder<static>|FundRequest whereIdentityId($value)
 * @method static Builder<static>|FundRequest whereNote($value)
 * @method static Builder<static>|FundRequest whereResolvedAt($value)
 * @method static Builder<static>|FundRequest whereState($value)
 * @method static Builder<static>|FundRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequest extends BaseModel
{
    use HasLogs;
    use HasNotes;

    public const string EVENT_CREATED = 'created';
    public const string EVENT_APPROVED = 'approved';
    public const string EVENT_DECLINED = 'declined';
    public const string EVENT_RESOLVED = 'resolved';
    public const string EVENT_ASSIGNED = 'assigned';
    public const string EVENT_RESIGNED = 'resigned';
    public const string EVENT_DISREGARDED = 'disregarded';

    public const string STATE_PENDING = 'pending';
    public const string STATE_APPROVED = 'approved';
    public const string STATE_DECLINED = 'declined';
    public const string STATE_DISREGARDED = 'disregarded';

    public const array EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_APPROVED,
        self::EVENT_DECLINED,
        self::EVENT_RESOLVED,
        self::EVENT_ASSIGNED,
        self::EVENT_RESIGNED,
    ];

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_DISREGARDED,
    ];

    public const array STATES_RESOLVED = [
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_DISREGARDED,
    ];

    public const array STATES_ARCHIVED = [
        self::STATE_DECLINED,
    ];

    protected $fillable = [
        'fund_id', 'employee_id', 'note', 'state', 'resolved_at',
        'disregard_note', 'disregard_notify', 'identity_id', 'contact_information',
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
        return $this->belongsTo(Identity::class);
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
        return (int) ($this->resolved_at ?: now())->diffInDays($this->created_at);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getLeadTimeLocaleAttribute(): string
    {
        return ($this->resolved_at ?: now())->diffForHumans($this->created_at, [
            'parts' => 5,
            'join' => ', ',
            'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            'skip' => ['seconds', 'weeks'],
        ]);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return match ($this->state) {
            self::STATE_PENDING => trans('states.fund_requests.pending'),
            self::STATE_APPROVED => trans('states.fund_requests.approved'),
            self::STATE_DECLINED => trans('states.fund_requests.declined'),
            self::STATE_DISREGARDED => trans('states.fund_requests.disregarded'),
            default => $this->state,
        };
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function physical_card_requests(): HasMany
    {
        return $this->hasMany(PhysicalCardRequest::class);
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
     * @throws Throwable
     * @return FundRequest
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
     * Set all fund request pending records assigned to given employee as disregarded.
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
        $this->update([
            'state' => FundRequest::STATE_PENDING,
        ]);

        return $this;
    }

    /**
     * Assign all available pending fund request records to given employee.
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
     * Remove all assigned fund request records from employee.
     * @param Employee $employee
     * @param Employee|null $supervisorEmployee
     * @return $this
     */
    public function resignEmployee(Employee $employee, ?Employee $supervisorEmployee = null): self
    {
        $this->records()->where([
            'record_type_key' => 'partner_bsn',
        ])->forceDelete();

        $this->update([
            'employee_id' => null,
        ]);

        FundRequestResigned::dispatch($this, $employee, $supervisorEmployee);

        return $this;
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
    public function formulaPreview(): array
    {
        $totalAmount = 0;
        $values = $this->getTrustedAndPendingRecordValues();

        $products = $this->fund->fund_formula_products->sortByDesc('product_id')->map(fn ($formula) => [
            'record' => $formula->record_type ? $formula->record_type->name : 'Product tegoed',
            'type' => $formula->record_type_key_multiplier ? 'Multiply' : 'Vastgesteld',
            'value' => $formula->product->name,
            'count' => $formula->record_type_key_multiplier ? Arr::get($values, $formula->record_type_key_multiplier) : 1,
            'total' => $formula->product->name,
        ]);

        $formula = $this->fund->fund_formulas->map(function ($formula) use ($values, &$totalAmount) {
            $count = $formula->record_type_key ? (float) Arr::get($values, $formula->record_type_key) : 1;
            $total = (float) $formula->amount * $count;
            $totalAmount += $total;

            return [
                'record' => $formula->record_type ? $formula->record_type->name : 'Vastbedrag',
                'type' => $formula->type_locale,
                'value' => $formula->amount_locale,
                'count' => $count,
                'total' => currency_format_locale($total),
            ];
        });

        return [
            'total_products' => $products->sum('count'),
            'total_amount' => currency_format_locale($totalAmount),
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
     * @param bool $useTrusted
     * @return string
     */
    public function getIban(bool $useTrusted = true): string
    {
        if ($useTrusted) {
            return $this->fund->getTrustedRecordOfType(
                $this->identity,
                $this->fund->fund_config->iban_record_key,
            )?->value ?: '';
        }

        return $this->records
            ?->firstWhere('record_type_key', $this->fund->fund_config->iban_record_key)
            ?->value ?: '';
    }

    /**
     * @param bool $useTrusted
     * @return string
     */
    public function getIbanName(bool $useTrusted = true): string
    {
        if ($useTrusted) {
            return $this->fund->getTrustedRecordOfType(
                $this->identity,
                $this->fund->fund_config->iban_name_record_key,
            )?->value ?: '';
        }

        return $this->records
            ?->firstWhere('record_type_key', $this->fund->fund_config->iban_name_record_key)
            ?->value ?: '';
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
                return 'invalid_iban_record_values';
            }

            if (Validation::check(Arr::get($values, $this->fund?->fund_config->iban_record_key), [
                'required', new IbanRule(),
            ])->fails()) {
                return 'invalid_iban_format';
            }

            return $this->fund->getResolvingError();
        }

        return null;
    }

    /**
     * @param array $address
     * @return PhysicalCardRequest
     */
    public function makePhysicalCardRequest(array $address): PhysicalCardRequest
    {
        /** @var PhysicalCardRequest $cardRequest */
        $cardRequest = $this->physical_card_requests()->create(Arr::only($address, [
            'address', 'house', 'house_addition', 'postcode', 'city', 'employee_id', 'physical_card_type_id',
        ]));

        Event::dispatch(new FundRequestPhysicalCardRequestEvent($this, $cardRequest));

        return $cardRequest;
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

        $trustedValues = $this->fund->getTrustedRecordOfTypes($this->identity, $recordTypes);

        return  [
            ...$trustedValues,
            ...$this->records->pluck('value', 'record_type_key')->toArray(),
        ];
    }
}
