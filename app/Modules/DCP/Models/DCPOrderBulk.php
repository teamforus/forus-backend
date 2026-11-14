<?php

namespace App\Modules\DCP\Models;

use App\Models\Employee;
use App\Models\Organization;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Services\EventLogService\Traits\HasLogs;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $employee_id
 * @property Carbon $approved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Employee|null $employee
 */
class DCPOrderBulk extends Model
{
    use HasLogs;

    protected $table = 'dcp_order_bulks';

    protected $fillable = ['organization_id', 'employee_id', 'approved_at'];

    protected $casts = ['approved_at' => 'datetime'];

    protected $perPage = 10;

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
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasMany
     */
    public function dcp_orders(): HasMany
    {
        return $this->hasMany(DCPOrder::class, 'dcp_order_bulk_id');
    }

    /**
     * @return array<int|string, string|Closure>
     */
    public static function orderCounts(): array
    {
        $counts = [
            'dcp_orders',
            'dcp_orders as dcp_orders_shipped_count' => function (Builder $query) {
                $query->whereNotNull('shipped_at');
            },
        ];

        foreach (DCPOrderState::cases() as $state) {
            $counts['dcp_orders as dcp_orders_' . $state->value . '_count'] = function (Builder $query) use ($state) {
                $query->where('state', $state->value);
            };
        }

        return $counts;
    }
}
