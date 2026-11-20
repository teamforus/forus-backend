<?php

namespace App\Modules\RequesterImport\Models;

use App\Models\Employee;
use App\Models\ProfileRecord;
use App\Models\RecordType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $requester_import_row_id
 * @property int $organization_id
 * @property int|null $employee_id
 * @property string $record_type_key
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $synced_at
 */
class RequesterImportRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'requester_import_row_id', 'organization_id', 'employee_id', 'record_type_key', 'value', 'synced_at',
    ];

    protected $hidden = ['current_version', 'current_client_number'];

    protected $casts = ['synced_at' => 'datetime'];

    /**
     * @return MorphMany
     */
    public function profile_records(): MorphMany
    {
        return $this->morphMany(ProfileRecord::class, 'origin');
    }

    /**
     * @return BelongsTo
     */
    public function requester_import_row(): BelongsTo
    {
        return $this->belongsTo(RequesterImportRow::class)->withTrashed();
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_key', 'key');
    }

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
