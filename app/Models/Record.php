<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * App\Models\Record.
 *
 * @property int $id
 * @property string $identity_address
 * @property int $record_type_id
 * @property int|null $record_category_id
 * @property int|null $prevalidation_id
 * @property int|null $fund_request_id
 * @property int|null $organization_id
 * @property string $value
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Identity $identity
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\Prevalidation|null $prevalidation
 * @property-read \App\Models\RecordCategory|null $record_category
 * @property-read \App\Models\RecordType $record_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RecordValidation[] $validations
 * @property-read int|null $validations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RecordValidation[] $validations_approved
 * @property-read int|null $validations_approved_count
 * @method static Builder<static>|Record newModelQuery()
 * @method static Builder<static>|Record newQuery()
 * @method static Builder<static>|Record onlyTrashed()
 * @method static Builder<static>|Record query()
 * @method static Builder<static>|Record whereCreatedAt($value)
 * @method static Builder<static>|Record whereDeletedAt($value)
 * @method static Builder<static>|Record whereFundRequestId($value)
 * @method static Builder<static>|Record whereId($value)
 * @method static Builder<static>|Record whereIdentityAddress($value)
 * @method static Builder<static>|Record whereOrder($value)
 * @method static Builder<static>|Record whereOrganizationId($value)
 * @method static Builder<static>|Record wherePrevalidationId($value)
 * @method static Builder<static>|Record whereRecordCategoryId($value)
 * @method static Builder<static>|Record whereRecordTypeId($value)
 * @method static Builder<static>|Record whereUpdatedAt($value)
 * @method static Builder<static>|Record whereValue($value)
 * @method static Builder<static>|Record withTrashed()
 * @method static Builder<static>|Record withoutTrashed()
 * @mixin \Eloquent
 */
class Record extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'record_type_id', 'record_category_id',
        'value', 'order', 'prevalidation_id', 'fund_request_id', 'organization_id',
    ];

    protected $perPage = 1000;

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function prevalidation(): BelongsTo
    {
        return $this->belongsTo(Prevalidation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function record_category(): BelongsTo
    {
        return $this->belongsTo(RecordCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function validations(): HasMany
    {
        return $this->hasMany(RecordValidation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function validations_approved(): HasMany
    {
        return $this->hasMany(RecordValidation::class)->where([
            'state' => 'approved',
        ])->orderByDesc('updated_at');
    }

    /**
     * @return Model|RecordValidation
     */
    public function makeValidationRequest(): Model|RecordValidation
    {
        return $this->validations()->create([
            'uuid' => token_generator()->generate(64),
            'state' => 'pending',
            'identity_address' => null,
        ]);
    }

    /**
     * @param Builder|Relation|null $builder
     * @param array $filters
     * @param bool $hideSystemRecords
     * @return Builder|Relation|Record
     */
    public static function search(
        Builder|Relation $builder = null,
        array $filters = [],
        bool $hideSystemRecords = false
    ): Builder|Relation|Record {
        $builder = $builder ?: static::query();

        if (Arr::has($filters, 'type')) {
            $builder->whereRelation('record_type', 'key', '=', Arr::get($filters, 'type'));
        }

        if ($hideSystemRecords) {
            $builder->whereRelation('record_type', 'system', '=', false);
        }

        if (Arr::has($filters, 'record_category_id')) {
            $builder->where('record_category_id', '=', Arr::get($filters, 'record_category_id'));
        }

        return $builder->orderBy('order');
    }
}
