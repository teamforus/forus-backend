<?php

namespace App\Models;

use App\Events\FundRequests\FundRequestResolved;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * App\Models\FundRequest
 *
 * @property int $id
 * @property int $fund_id
 * @property int|null $employee_id
 * @property string $identity_address
 * @property string $note
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestClarification[] $clarifications
 * @property-read int|null $clarifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestClarification[] $clarifications_answered
 * @property-read int|null $clarifications_answered_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestClarification[] $clarifications_pending
 * @property-read int|null $clarifications_pending_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $records
 * @property-read int|null $records_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $records_approved
 * @property-read int|null $records_approved_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $records_declined
 * @property-read int|null $records_declined_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $records_pending
 * @property-read int|null $records_pending_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequest extends Model
{
    const STATE_PENDING = 'pending';
    const STATE_APPROVED = 'approved';
    const STATE_DECLINED = 'declined';
    const STATE_APPROVED_PARTLY = 'approved_partly';

    const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_APPROVED_PARTLY,
    ];

    protected $fillable = [
        'fund_id', 'identity_address', 'employee_id', 'note', 'state',
    ];

    /**
     * @param \Illuminate\Http\Request $request
     * @param Organization|null $organization
     * @return FundRequest|\Illuminate\Database\Eloquent\Builder
     */
    public static function search(
        Request $request,
        Organization $organization = null
    ) {
        $query = self::query();

        if ($organization) {
            $query->whereIn('fund_id', $organization->funds()->pluck('id'));
        }

        if ($request->has('q') && $q = $request->input('q')) {
            $query->whereHas('fund', function(Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
            });
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records()
    {
        return $this->hasMany(FundRequestRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function clarifications()
    {
        return $this->hasManyThrough(FundRequestClarification::class, FundRequestRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records_approved()
    {
        return $this->records()->where([
            'fund_request_records.state' => FundRequestRecord::STATE_APPROVED
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records_declined()
    {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_records.state' => FundRequestRecord::STATE_DECLINED
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records_pending()
    {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_records.state' => FundRequestRecord::STATE_PENDING
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clarifications_pending()
    {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_clarifications.state' => FundRequestClarification::STATE_PENDING
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clarifications_answered()
    {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_clarifications.state' => FundRequestClarification::STATE_ANSWERED
        ]);
    }

    /**
     * @param string|null $note
     * @return FundRequest
     * @throws \Exception
     */
    public function decline(string $note = null) {
        if (!$this->employee_id) {
            throw new \Exception("No employee assigned.", 403);
        }

        foreach ($this->records_pending as $record) {
            $record->decline($note, false);
        }

        $this->records_approved()->each(function(FundRequestRecord $record) {
            $record->makeValidation();
        });

        return $this->updateStateByRecords()->updateModel([
            'note' => $note,
        ]);
    }

    /**
     * @return FundRequest
     * @throws \Exception
     */
    public function approve() {
        if (!$this->employee_id) {
            throw new \Exception("No employee assigned.", 403);
        }

        $this->records_pending()->each(function(FundRequestRecord $record) {
            $record->approve(false);
        });

        $this->records_approved()->each(function(FundRequestRecord $record) {
            $record->makeValidation();
        });

        return $this->updateStateByRecords();
    }

    /**
     * @param string $state
     * @param string|null $note
     * @return $this|FundRequest
     * @throws \Exception
     */
    public function resolve(string $state, string $note = null)
    {
        switch ($state) {
            case self::STATE_APPROVED: {
                return $this->approve();
            } break;
            case self::STATE_DECLINED: {
                return $this->decline($note);
            } break;
        }

        return $this;
    }

    public function updateStateByRecords() {
        $countAll = $this->records()->count();
        $countApproved = $this->records_approved()->count();
        $allApproved = $countAll == $countApproved;
        $hasApproved = $countApproved > 0;
        $oldState = $this->state;

        $this->update([
            'state' => $allApproved ? self::STATE_APPROVED : (
                $hasApproved ? self::STATE_APPROVED_PARTLY : self::STATE_DECLINED
            )
        ]);

        if (($oldState !== $this->state) && ($this->state != 'pending')) {
            FundRequestResolved::dispatch($this);
        }

        return $this;
    }

    /**
     * @param Employee $employee
     * @return FundRequest
     */
    public function assignEmployee(Employee $employee) {
        return $this->updateModel([
            'employee_id' => $employee->id
        ]);
    }

    /**
     * @return FundRequest
     */
    public function resignEmployee() {
        return $this->updateModel([
            'employee_id' => null
        ]);
    }
}
