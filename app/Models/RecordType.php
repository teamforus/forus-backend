<?php

namespace App\Models;

use App\Models\Traits\Translations\RecordTranslationsTrait;
use App\Services\TranslationService\Traits\HasTranslationCaches;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * App\Models\RecordType
 *
 * @property int $id
 * @property string $key
 * @property string $type
 * @property string $control_type
 * @property int|null $organization_id
 * @property bool $system
 * @property bool $criteria
 * @property bool $vouchers
 * @property bool $pre_check
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Models\FundCriterion[] $fund_criteria
 * @property-read int|null $fund_criteria_count
 * @property-read \App\Models\Organization|null $organization
 * @property-read Collection|\App\Models\PreCheckRecord[] $pre_check_records
 * @property-read int|null $pre_check_records_count
 * @property-read Collection|\App\Models\RecordTypeOption[] $record_type_options
 * @property-read int|null $record_type_options_count
 * @property-read \App\Models\RecordTypeTranslation|null $translation
 * @property-read Collection|\App\Services\TranslationService\Models\TranslationCache[] $translation_caches
 * @property-read int|null $translation_caches_count
 * @property-read Collection|\App\Models\RecordTypeTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder<static>|RecordType listsTranslations(string $translationField)
 * @method static Builder<static>|RecordType newModelQuery()
 * @method static Builder<static>|RecordType newQuery()
 * @method static Builder<static>|RecordType notTranslatedIn(?string $locale = null)
 * @method static Builder<static>|RecordType orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static Builder<static>|RecordType orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder<static>|RecordType orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static Builder<static>|RecordType query()
 * @method static Builder<static>|RecordType translated()
 * @method static Builder<static>|RecordType translatedIn(?string $locale = null)
 * @method static Builder<static>|RecordType whereControlType($value)
 * @method static Builder<static>|RecordType whereCreatedAt($value)
 * @method static Builder<static>|RecordType whereCriteria($value)
 * @method static Builder<static>|RecordType whereId($value)
 * @method static Builder<static>|RecordType whereKey($value)
 * @method static Builder<static>|RecordType whereOrganizationId($value)
 * @method static Builder<static>|RecordType wherePreCheck($value)
 * @method static Builder<static>|RecordType whereSystem($value)
 * @method static Builder<static>|RecordType whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static Builder<static>|RecordType whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder<static>|RecordType whereType($value)
 * @method static Builder<static>|RecordType whereUpdatedAt($value)
 * @method static Builder<static>|RecordType whereVouchers($value)
 * @method static Builder<static>|RecordType withTranslation(?string $locale = null)
 * @mixin \Eloquent
 */
class RecordType extends BaseModel
{
    use Translatable, RecordTranslationsTrait, HasTranslationCaches;

    public const string TYPE_BOOL = 'bool';
    public const string TYPE_IBAN = 'iban';
    public const string TYPE_DATE = 'date';
    public const string TYPE_EMAIL = 'email';
    public const string TYPE_STRING = 'string';
    public const string TYPE_NUMBER = 'number';
    public const string TYPE_SELECT = 'select';
    public const string TYPE_SELECT_NUMBER = 'select_number';

    public const array TYPES = [
        self::TYPE_BOOL,
        self::TYPE_IBAN,
        self::TYPE_DATE,
        self::TYPE_EMAIL,
        self::TYPE_STRING,
        self::TYPE_NUMBER,
        self::TYPE_SELECT,
        self::TYPE_SELECT_NUMBER,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'type', 'system', 'criteria', 'vouchers', 'organization_id', 'control_type',
    ];

    protected $perPage = 100;

    /**
     * The attributes that are translatable.
     *
     * @var array
     * @noinspection PhpUnused
     */
    public array $translatedAttributes = [
        'name',
    ];

    protected $casts = [
        'system' => 'bool',
        'criteria' => 'bool',
        'vouchers' => 'bool',
        'pre_check' => 'bool',
    ];

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function fund_criteria(): HasMany
    {
        return $this->hasMany(FundCriterion::class, 'record_type_key', 'key');
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function pre_check_records(): HasMany
    {
        return $this->hasMany(PreCheckRecord::class, 'record_type_key', 'key');
    }

    /**
     * @param bool $withSystem
     * @param array $filters
     * @return RecordType|Builder
     */
    public static function searchQuery(array $filters = [], bool $withSystem = true): RecordType|Builder
    {
        /** @var RecordType $query */
        $query = static::where(fn(Builder $builder) => $builder->where($withSystem ? [] : [
            'system' => false,
        ]))->with('translations');

        if (Arr::get($filters, 'vouchers', false)) {
            $query->where('vouchers', true);
        }

        if (Arr::get($filters, 'criteria', false)) {
            $query->where('criteria', true);
        }

        if (Arr::get($filters, 'organization_id', false)) {
            $query->where(function(Builder|RecordType $builder) use ($filters) {
                $builder->whereNull('organization_id');
                $builder->orWhere('organization_id', Arr::get($filters, 'organization_id'));
            });
        }

        return $query;
    }

    /**
     * @param bool $withSystem
     * @param array $filters
     * @return Collection|RecordType
     */
    public static function search(bool $withSystem = true, array $filters = []): Collection|array
    {
        return static::searchQuery($filters, $withSystem)->get();
    }

    /**
     * @param string $key
     * @return RecordType|null
     */
    public static function findByKey(string $key): ?RecordType
    {
        return static::where('key', $key)->first();
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function record_type_options(): HasMany
    {
        return $this->hasMany(RecordTypeOption::class);
    }

    /**
     * @return array
     */
    public function getValidations(): array
    {
        return array_keys(array_filter([
            'date' => $this->type == 'date',
            'email' => $this->type == 'email',
            'iban' => $this->type == 'iban',
            'min' => in_array($this->type, ['string', 'number', 'date'], true),
            'max' => in_array($this->type, ['string', 'number', 'date'], true),
        ]));
    }

    /**
     * @return array
     */
    public function getOperators(): array
    {
        return match ($this->type) {
            self::TYPE_DATE,
            self::TYPE_NUMBER => ['<', '<=', '=', '>=', '>', '*'],
            self::TYPE_SELECT_NUMBER => ['<=', '=', '>=', '*'],
            self::TYPE_BOOL,
            self::TYPE_STRING,
            self::TYPE_SELECT => ['=', '*'],
            self::TYPE_IBAN,
            self::TYPE_EMAIL => ['*'],
            default => [],
        };
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        if ($this->type == 'bool') {
            return [
                ['value' => 'Ja', 'name' => trans('record_types.options.yes')],
                ['value' => 'Nee', 'name' =>  trans('record_types.options.no')],
            ];
        }

        return $this->record_type_options->map(fn (RecordTypeOption $option) => [
            'value' => $option->value,
            'name' => $option->name,
            ...$option->translateColumns($option->only('name')),
        ])->toArray();
    }
}
