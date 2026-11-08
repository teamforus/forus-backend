<?php

namespace App\Services\DCPService\Models;

use App\Events\DCPOrders\DCPOrderApproved;
use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * App\Services\DCPService\Models\DCPOrderBulk.
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $employee_id
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|DCPOrder[] $dcp_orders
 * @property-read int|null $dcp_orders_count
 * @property-read int|null $dcp_orders_pending_count
 * @property-read int|null $dcp_orders_sent_count
 * @property-read int|null $dcp_orders_error_count
 * @property-read Employee|null $employee
 * @property-read Organization $organization
 * @mixin \Eloquent
 */
class DCPOrderBulk extends Model
{
    public const string STATE_PENDING = 'pending';
    public const string STATE_SENT = 'sent';
    public const string STATE_ERROR = 'error';

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_SENT,
        self::STATE_ERROR,
    ];

    protected $table = 'dcp_order_bulks';

    /**
     * @var string[]
     */
    protected $fillable = [
        'organization_id', 'employee_id', 'state', 'processed_at',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function dcp_orders(): HasMany
    {
        return $this->hasMany(DCPOrder::class, 'dcp_order_bulk_id');
    }

    /**
     * @return array
     */
    public static function orderCounts(): array
    {
        return [
            'dcp_orders',
            'dcp_orders as dcp_orders_pending_count' => fn (Builder $builder) => $builder->where(
                'state',
                DCPOrder::STATE_PENDING,
            ),
            'dcp_orders as dcp_orders_sent_count' => fn (Builder $builder) => $builder->whereIn('state', [
                DCPOrder::STATE_SENT, DCPOrder::STATE_CLOSED,
            ]),
            'dcp_orders as dcp_orders_error_count' => fn (Builder $builder) => $builder->whereIn('state', [
                DCPOrder::STATE_ERROR, DCPOrder::STATE_CANCELED,
            ]),
        ];
    }

    /**
     * @param Organization $organization
     * @param Employee $employee
     * @return DCPOrderBulk|null
     */
    public static function makeBulk(Organization $organization, Employee $employee): ?DCPOrderBulk
    {
        return Cache::lock("dcp_order_bulk_create_$organization->id", 60)->get(function () use (
            $organization,
            $employee,
        ) {
            return DB::transaction(function () use ($organization, $employee) {
                if (!DCPOrder::queryToApprove($organization)->exists()) {
                    return null;
                }

                $bulk = self::create([
                    'organization_id' => $organization->id,
                    'employee_id' => $employee->id,
                    'state' => self::STATE_PENDING,
                ]);

                DCPOrder::queryToApprove($organization)->update([
                    'dcp_order_bulk_id' => $bulk->id,
                ]);

                $bulk->dcp_orders()->get()->each(fn (DCPOrder $order) => DCPOrderApproved::dispatch(
                    $order,
                    $employee,
                ));

                return $bulk;
            });
        }) ?: null;
    }

    /**
     * @return $this
     */
    public function updateState(): self
    {
        if (DCPOrder::whereCanBeSent($this->dcp_orders()->getQuery())->exists()) {
            return $this;
        }

        $hasErrors = $this->dcp_orders()->where('state', DCPOrder::STATE_ERROR)->exists();

        return tap($this)->update([
            'state' => $hasErrors ? self::STATE_ERROR : self::STATE_SENT,
            'processed_at' => $this->processed_at ?: now(),
        ]);
    }

    /**
     * @return string
     */
    public function stateLocale(): string
    {
        return trans("dcp.bulk_states.$this->state");
    }
}
