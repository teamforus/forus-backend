<?php

namespace App\Modules\DCP\Models;

use App\Models\Employee;
use App\Models\Organization;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $dcp_connection_id
 * @property int|null $dcp_order_bulk_id
 * @property int|null $employee_id
 * @property string $orderable_type
 * @property int $orderable_id
 * @property string $order_id
 * @property string|null $order_id_dcp
 * @property string $label
 * @property string $delivery_first_name
 * @property string $delivery_last_name
 * @property string $delivery_address1
 * @property string $delivery_postal_code
 * @property string $delivery_city
 * @property string $delivery_country
 * @property array $order_rows
 * @property DCPOrderState $state
 * @property int|null $status_id
 * @property string|null $status_message
 * @property string|null $diagnostic_id
 * @property array|null $submission_response
 * @property array|null $status_response
 * @property Carbon|null $approved_at
 * @property Carbon|null $sending_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $last_status_checked_at
 * @property Carbon|null $shipped_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read DCPConnection $dcp_connection
 * @property-read DCPOrderBulk|null $dcp_order_bulk
 * @property-read Employee|null $employee
 * @property-read Model|null $orderable
 */
class DCPOrder extends Model
{
    use HasLogs;

    protected $table = 'dcp_orders';

    protected $fillable = [
        'organization_id', 'dcp_connection_id', 'dcp_order_bulk_id', 'employee_id', 'orderable_type', 'orderable_id',
        'order_id', 'order_id_dcp', 'label', 'delivery_first_name', 'delivery_last_name', 'delivery_address1',
        'delivery_postal_code', 'delivery_city', 'delivery_country', 'order_rows', 'state', 'status_id',
        'status_message', 'diagnostic_id', 'submission_response', 'status_response', 'approved_at', 'sending_at',
        'submitted_at', 'last_status_checked_at', 'shipped_at',
    ];

    protected $hidden = ['submission_response', 'status_response'];

    protected $perPage = 10;

    protected $casts = [
        'state' => DCPOrderState::class,
        'order_rows' => 'array',
        'submission_response' => 'array',
        'status_response' => 'array',
        'approved_at' => 'datetime',
        'sending_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_status_checked_at' => 'datetime',
        'shipped_at' => 'datetime',
    ];

    /**
     * @return MorphTo
     */
    public function orderable(): MorphTo
    {
        return $this->morphTo();
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
    public function dcp_connection(): BelongsTo
    {
        return $this->belongsTo(DCPConnection::class);
    }

    /**
     * @return BelongsTo
     */
    public function dcp_order_bulk(): BelongsTo
    {
        return $this->belongsTo(DCPOrderBulk::class);
    }

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return bool
     */
    public function canUpdateDraft(): bool
    {
        return $this->state === DCPOrderState::DRAFT && $this->dcp_order_bulk_id === null;
    }

    /**
     * @return string
     */
    public function printCode(): string
    {
        return $this->order_rows[0]['tagIds'][0]['printedCardNumber'];
    }
}
