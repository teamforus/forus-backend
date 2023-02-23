<?php

namespace App\Models;

use App\Helpers\Validation;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\VoucherRecord
 *
 * @property int $id
 * @property int $voucher_id
 * @property int $record_type_id
 * @property string $value
 * @property string|null $note
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $value_locale
 * @property-read \App\Models\RecordType $record_type
 * @property-read \App\Models\Voucher $voucher
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord newQuery()
 * @method static \Illuminate\Database\Query\Builder|VoucherRecord onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VoucherRecord whereVoucherId($value)
 * @method static \Illuminate\Database\Query\Builder|VoucherRecord withTrashed()
 * @method static \Illuminate\Database\Query\Builder|VoucherRecord withoutTrashed()
 * @mixin \Eloquent
 */
class VoucherRecord extends Model
{
    use SoftDeletes, HasLogs;

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';

    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_UPDATED,
        self::EVENT_DELETED,
    ];

    protected $fillable = [
        'voucher_id', 'record_type_id', 'value', 'note',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getValueLocaleAttribute(): string
    {
        if ($this->record_type->key == 'birth_date') {
            return format_date_locale($this->value);
        }

        return $this->value;
    }

    /**
     * @param string $key
     * @param string|null $value
     * @return string|null
     */
    public static function validateRecord(string $key, ?string $value): ?string
    {
        $recordType = RecordType::findByKey($key);

        if (!$recordType) {
            return trans('validation.in', ['attribute' => $key]);
        }

        $check = Validation::check($value, match($recordType->key) {
            'birth_date' => 'required|date|date_format:Y-m-d',
            default => 'required|string|max:8000',
        }, $recordType->name);

        return $check->fails() ? $check->errors()->first() : null;
    }
}
