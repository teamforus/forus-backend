<?php

namespace App\Models;

use App\Models\Traits\Translations\RecordTranslationsTrait;
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
 * @property int|null $organization_id
 * @property bool $system
 * @property bool $criteria
 * @property bool $vouchers
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Models\FundCriterion[] $fund_criteria
 * @property-read int|null $fund_criteria_count
 * @property-read \App\Models\Organization|null $organization
 * @property-read Collection|\App\Models\RecordTypeOption[] $record_type_options
 * @property-read int|null $record_type_options_count
 * @property-read \App\Models\RecordTypeTranslation|null $translation
 * @property-read Collection|\App\Models\RecordTypeTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|RecordType listsTranslations(string $translationField)
 * @method static Builder|RecordType newModelQuery()
 * @method static Builder|RecordType newQuery()
 * @method static Builder|RecordType notTranslatedIn(?string $locale = null)
 * @method static Builder|RecordType orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static Builder|RecordType orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder|RecordType orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static Builder|RecordType query()
 * @method static Builder|RecordType translated()
 * @method static Builder|RecordType translatedIn(?string $locale = null)
 * @method static Builder|RecordType whereCreatedAt($value)
 * @method static Builder|RecordType whereCriteria($value)
 * @method static Builder|RecordType whereId($value)
 * @method static Builder|RecordType whereKey($value)
 * @method static Builder|RecordType whereOrganizationId($value)
 * @method static Builder|RecordType whereSystem($value)
 * @method static Builder|RecordType whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static Builder|RecordType whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder|RecordType whereType($value)
 * @method static Builder|RecordType whereUpdatedAt($value)
 * @method static Builder|RecordType whereVouchers($value)
 * @method static Builder|RecordType withTranslation()
 * @mixin \Eloquent
 */
class RecordType extends BaseModel
{
    use Translatable, RecordTranslationsTrait;

    public const TYPE_BOOL = 'bool';
    public const TYPE_IBAN = 'iban';
    public const TYPE_DATE = 'date';
    public const TYPE_EMAIL = 'email';
    public const TYPE_STRING = 'string';
    public const TYPE_NUMBER = 'number';
    public const TYPE_SELECT = 'select';

    public const TYPES = [
        self::TYPE_BOOL,
        self::TYPE_IBAN,
        self::TYPE_DATE,
        self::TYPE_EMAIL,
        self::TYPE_STRING,
        self::TYPE_NUMBER,
        self::TYPE_SELECT,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'type', 'system', 'criteria', 'vouchers', 'organization_id',
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
    ];

    /**
     * @return HasMany
     */
    public function fund_criteria(): HasMany
    {
        return $this->hasMany(FundCriterion::class, 'record_type_key', 'key');
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
        if (in_array($this->type, ['number', 'date'], true)) {
            return ['<', '<=', '=', '>=', '>', '*'];
        }

        if (in_array($this->type, ['string', 'select', 'bool'], true)) {
            return ['=', '*'];
        }

        if (in_array($this->type, ['iban', 'email'], true)) {
            return ['*'];
        }

        return [];
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        if ($this->type == 'bool') {
            return [
                ['value' => 'Ja', 'name' =>  'Ja'],
                ['value' => 'Nee', 'name' =>  'Nee'],
            ];
        }

        return $this->record_type_options->map(fn (RecordTypeOption $option) => [
            'value' => $option->value,
            'name' => $option->name,
        ])->toArray();
    }
}
