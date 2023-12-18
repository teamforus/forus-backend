<?php

namespace App\Models;

use App\Events\Reimbursements\ReimbursementAssigned;
use App\Events\Reimbursements\ReimbursementResigned;
use App\Events\Reimbursements\ReimbursementResolved;
use App\Models\Traits\HasNotes;
use App\Models\Traits\HasTags;
use App\Models\Traits\UpdatesModel;
use App\Searches\ReimbursementsSearch;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\FileService\Traits\HasFiles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * App\Models\Reimbursement
 *
 * @property int $id
 * @property int $voucher_id
 * @property int|null $employee_id
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property string $amount
 * @property string $reason
 * @property string $iban
 * @property string $iban_name
 * @property string|null $provider_name
 * @property int|null $reimbursement_category_id
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\FileService\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read string|null $amount_locale
 * @property-read bool $deactivated
 * @property-read \Carbon\Carbon|null $expire_at
 * @property-read string|null $expire_at_locale
 * @property-read bool $expired
 * @property-read int|null $lead_time_days
 * @property-read string $lead_time_locale
 * @property-read string|null $resolved_at_locale
 * @property-read string $state_locale
 * @property-read string|null $submitted_at_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Note[] $notes
 * @property-read int|null $notes_count
 * @property-read \App\Models\ReimbursementCategory|null $reimbursement_category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\Voucher $voucher
 * @property-read \App\Models\VoucherTransaction|null $voucher_transaction
 * @method static Builder|Reimbursement newModelQuery()
 * @method static Builder|Reimbursement newQuery()
 * @method static Builder|Reimbursement onlyTrashed()
 * @method static Builder|Reimbursement query()
 * @method static Builder|Reimbursement whereAmount($value)
 * @method static Builder|Reimbursement whereCode($value)
 * @method static Builder|Reimbursement whereCreatedAt($value)
 * @method static Builder|Reimbursement whereDeletedAt($value)
 * @method static Builder|Reimbursement whereDescription($value)
 * @method static Builder|Reimbursement whereEmployeeId($value)
 * @method static Builder|Reimbursement whereIban($value)
 * @method static Builder|Reimbursement whereIbanName($value)
 * @method static Builder|Reimbursement whereId($value)
 * @method static Builder|Reimbursement whereProviderName($value)
 * @method static Builder|Reimbursement whereReason($value)
 * @method static Builder|Reimbursement whereReimbursementCategoryId($value)
 * @method static Builder|Reimbursement whereResolvedAt($value)
 * @method static Builder|Reimbursement whereState($value)
 * @method static Builder|Reimbursement whereSubmittedAt($value)
 * @method static Builder|Reimbursement whereTitle($value)
 * @method static Builder|Reimbursement whereUpdatedAt($value)
 * @method static Builder|Reimbursement whereVoucherId($value)
 * @method static Builder|Reimbursement withTrashed()
 * @method static Builder|Reimbursement withoutTrashed()
 * @mixin \Eloquent
 */
class Reimbursement extends Model
{
    use SoftDeletes, HasFiles, HasNotes, HasTags, UpdatesModel, HasLogs;

    public const STATE_DRAFT = 'draft';
    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';

    public const EVENT_CREATED = 'created';
    public const EVENT_SUBMITTED = 'submitted';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_DECLINED = 'declined';
    public const EVENT_RESOLVED = 'resolved';
    public const EVENT_ASSIGNED = 'assigned';
    public const EVENT_RESIGNED = 'resigned';

    public const STATES_RESOLVED = [
        self::STATE_APPROVED,
        self::STATE_DECLINED,
    ];

    /**
     * @noinspection PhpUnused
     */
    public const STATES = [
        self::STATE_DRAFT,
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'voucher_id', 'title', 'description', 'amount', 'iban', 'iban_name',
        'state', 'code', 'employee_id', 'submitted_at', 'resolved_at',
        'reason', 'provider_name', 'reimbursement_category_id',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'iban', 'iban_name',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'resolved_at',
        'submitted_at',
    ];

    /**
     * @return BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
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
     * @noinspection PhpUnused
     */
    public function reimbursement_category(): BelongsTo
    {
        return $this->belongsTo(ReimbursementCategory::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function voucher_transaction(): HasOne
    {
        return $this->hasOne(VoucherTransaction::class);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getExpiredAttribute(): bool
    {
        return !$this->isResolved() && $this->voucher->expired;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getDeactivatedAttribute(): bool
    {
        return !$this->isResolved() && $this->voucher->isDeactivated();
    }

    /**
     * @return Carbon|null
     * @noinspection PhpUnused
     */
    public function getExpireAtAttribute(): ?Carbon
    {
        if ($this->isResolved()) {
            return null;
        }

        if ($this->voucher->expire_at->isAfter($this->voucher->fund->end_date)) {
            return $this->voucher->fund->end_date;
        }

        return $this->voucher->expire_at;
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
            self::STATE_DRAFT => 'Nog niet ingediend',
            self::STATE_PENDING => 'In afwachting',
            self::STATE_APPROVED => 'Uitbetaald',
            self::STATE_DECLINED => 'Afgewezen',
        ][$this->state] ?? '';
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getAmountLocaleAttribute(): ?string
    {
        return currency_format_locale($this->amount, $this->voucher->fund->getImplementation());
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getSubmittedAtLocaleAttribute(): ?string
    {
        return format_date_locale($this->submitted_at);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getResolvedAtLocaleAttribute(): ?string
    {
        return format_date_locale($this->resolved_at);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getExpireAtLocaleAttribute(): ?string
    {
        return format_date_locale($this->expire_at);
    }

    /**
     * @throws \Exception
     */
    public static function makeCode(): int
    {
        do {
            $code = random_int(11111111, 99999999);
        } while(self::query()->where(compact('code'))->exists());

        return $code;
    }

    /**
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->state === self::STATE_DRAFT;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->state === self::STATE_APPROVED;
    }

    /**
     * @return bool
     */
    public function isDeclined(): bool
    {
        return $this->state === self::STATE_DECLINED;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expired;
    }

    /**
     * @param Employee $employee
     * @return $this
     */
    public function assign(Employee $employee): self
    {
        ReimbursementAssigned::broadcast($this->updateModel([
            'employee_id' => $employee->id,
        ]), $employee);

        return $this;
    }

    /**
     * @return $this
     */
    public function resign(): self
    {
        ReimbursementResigned::broadcast($this->updateModel([
            'employee_id' => null,
        ]));

        return $this;
    }

    /**
     * @param string|null $note
     * @return $this
     * @throws Throwable
     */
    public function approve(?string $note = null): self
    {
        DB::transaction(fn() => $this->resolve(true, $note));

        return $this;
    }

    /**
     * @return $this
     * @throws Throwable
     */
    public function decline(?string $note = null, ?string $reason = null): self
    {
        DB::transaction(fn() => $this->resolve(false, $note, $reason));

        return $this;
    }

    /**
     * @param bool $approved
     * @param string|null $note
     * @param string|null $reason
     * @return $this
     */
    protected function resolve(bool $approved, ?string $note = null, ?string $reason = null): self
    {
        if ($this->isResolved()) {
            return $this;
        }

        $this->update([
            'state' => $approved ? self::STATE_APPROVED : self::STATE_DECLINED,
            'reason' => $reason,
            'resolved_at' => now(),
        ]);

        if ($approved) {
            $this->makeTransaction();
        }

        if ($note) {
            $note = sprintf("%s: $note", $approved ? "Geaccepteerd" : "Afgewezen");
            $this->addNote($note, $this->employee);
        }

        ReimbursementResolved::dispatch($this, $this->employee);

        return $this;
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
     * @return VoucherTransaction
     */
    protected function makeTransaction(): VoucherTransaction
    {
        return $this->voucher->makeTransaction([
            'amount' => $this->amount,
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'employee_id' => $this->employee->id,
            'reimbursement_id' => $this->id,
            'target' => VoucherTransaction::TARGET_IBAN,
            'state' => VoucherTransaction::STATE_PENDING,
            'target_iban' => $this->iban,
            'target_name' => $this->iban_name
        ]);
    }

    /**
     * @param Builder $builder
     * @param array $fields
     * @return SupportCollection
     */
    private static function exportTransform(Builder $builder, array $fields): SupportCollection
    {
        $data = $builder->with([
            'reimbursement_category',
            'voucher.fund.organization',
            'voucher.identity.record_bsn',
            'voucher.identity.primary_email',
            'employee.identity.primary_email',
        ])->get();

        return $data->map(fn (Reimbursement $reimbursement) => array_only([
            'id' => $reimbursement->id,
            'code' => '#' . $reimbursement->code,
            'implementation_name' => $reimbursement->voucher->fund->fund_config?->implementation?->name,
            'fund_name' => $reimbursement->voucher->fund->name,
            'amount' => currency_format($reimbursement->amount),
            'employee' => $reimbursement->employee?->identity?->email ?: '-',
            'email' => $reimbursement->voucher->identity->email,
            'bsn' => $reimbursement->voucher->fund->organization->bsn_enabled ?
                ($reimbursement->voucher->identity->record_bsn?->value ?: '-') :
                '-',
            'iban' => $reimbursement->iban,
            'iban_name' => $reimbursement->iban_name,
            'provider_name' => $reimbursement->provider_name ?: '-',
            'category' => $reimbursement->reimbursement_category?->name ?: '-',
            'title' => $reimbursement->title,
            'description' => $reimbursement->description,
            'files_count' => $reimbursement->files_count,
            'submitted_at' => $reimbursement->submitted_at ?
                format_datetime_locale($reimbursement->submitted_at) :
                '-',
            'resolved_at' => $reimbursement->resolved_at ?
                format_datetime_locale($reimbursement->resolved_at) :
                '-',
            'lead_time' => $reimbursement->lead_time_locale,
            'expired' => $reimbursement->expired ? 'Ja' : 'Nee',
            'state' => $reimbursement->state_locale,
        ], $fields))->values();
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     * @return SupportCollection
     */
    public static function export(Request $request, Organization $organization, array $fields): SupportCollection
    {
        $query = Reimbursement::where('state', '!=', Reimbursement::STATE_DRAFT);
        $query = $query->whereRelation('voucher.fund', 'organization_id', $organization->id);

        $search = new ReimbursementsSearch($request->only([
            'q', 'fund_id', 'from', 'to', 'amount_min', 'amount_max', 'state',
            'expired', 'archived', 'deactivated', 'identity_address', 'implementation_id',
        ]), $query);

        return self::exportTransform($search->query()->latest(), $fields);
    }
}
