<?php

namespace App\Services\TranslationService\Models;

use App\Models\Announcement;
use App\Models\FundConfig;
use App\Models\FundCriteriaStep;
use App\Models\FundCriterion;
use App\Models\Language;
use App\Models\ReservationField;
use App\Models\PreCheck;
use App\Models\PreCheckRecord;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

/**
 * App\Services\TranslationService\Models\TransactionValue.
 *
 * @property int $id
 * @property string $translatable_type
 * @property int $translatable_id
 * @property string $key
 * @property string $locale
 * @property int|null $organization_id
 * @property int|null $implementation_id
 * @property string $from
 * @property int $from_length
 * @property string $to
 * @property int $to_length
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|Eloquent $translatable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereFromLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereToLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereTranslatableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereTranslatableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TranslationValue whereUpdatedAt($value)
 * @mixin Eloquent
 */
class TranslationValue extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'translatable_type', 'translatable_id', 'key', 'from', 'to', 'locale',
        'organization_id', 'implementation_id', 'from_length', 'to_length',
    ];

    protected static array $fieldMap = [
        'webshop_content' => 'Webshop content',
        'fund_application' => 'Aanvraagformulier',
        'providers_content' => 'Aanbieders content',
    ];

    /**
     * @return MorphTo
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Retrieves usage statistics for a given organization within a specified date range.
     *
     * @param int $organizationId The ID of the organization to filter by.
     * @param Carbon $from The start date of the date range.
     * @param Carbon $to The end date of the date range.
     * @param callable|null $callback An optional callback function to modify the query.
     * @return array An associative array containing total usage and grouped statistics.
     */
    public static function getUsage(int $organizationId, Carbon $from, Carbon $to, ?callable $callback = null): array
    {
        // Base query to filter by organization_id and date range
        $query = self::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfMonth()]);

        // Apply the callback if provided
        if ($callback) {
            $callback($query);
        }

        // Fetch raw data
        $results = $query->get(['translatable_type', 'locale', 'from_length']);

        // Initialize totals
        $totalSymbols = 0;
        $countPerType = [];
        $totalPerLocale = [];
        $totalPerTypeAndLocale = [];

        // Get all possible translatable types and locales
        $availableTypes = self::getAvailableTranslatableTypes();

        // Get type group mapping
        $typeGroups = self::getTranslatableTypeGroups();

        // Get all locales
        $availableLocales = self::getAvailableLocales();

        // Reverse the type group mapping for easier lookup
        $typeToGroupMap = [];

        foreach ($typeGroups as $group => $types) {
            foreach ($types as $type) {
                $typeToGroupMap[$type] = $group;
            }
        }

        // Process each row
        foreach ($results as $row) {
            $symbolCount = $row->from_length;
            $totalSymbols += $symbolCount;

            // Determine the final type key (grouped or original)
            $typeKey = $typeToGroupMap[$row->translatable_type] ?? $row->translatable_type;

            // Count per translatable_type
            if (!isset($countPerType[$typeKey])) {
                $countPerType[$typeKey] = 0;
            }

            $countPerType[$typeKey] += $symbolCount;

            // Count per locale
            if (!isset($totalPerLocale[$row->locale])) {
                $totalPerLocale[$row->locale] = 0;
            }

            $totalPerLocale[$row->locale] += $symbolCount;

            // Count per translatable_type + locale
            $typeAndLocaleKey = $typeKey . '_' . $row->locale;

            if (!isset($totalPerTypeAndLocale[$typeAndLocaleKey])) {
                $totalPerTypeAndLocale[$typeAndLocaleKey] = 0;
            }

            $totalPerTypeAndLocale[$typeAndLocaleKey] += $symbolCount;
        }

        // Ensure all keys exist
        foreach ($availableTypes as $type) {
            $typeKey = $typeToGroupMap[$type] ?? $type;

            if (!isset($countPerType[$typeKey])) {
                $countPerType[$typeKey] = 0;
            }

            foreach (array_keys($availableLocales) as $locale) {
                $key = $typeKey . '_' . $locale;

                if (!isset($totalPerTypeAndLocale[$key])) {
                    $totalPerTypeAndLocale[$key] = 0;
                }
            }
        }

        foreach (array_keys($availableLocales) as $locale) {
            if (!isset($totalPerLocale[$locale])) {
                $totalPerLocale[$locale] = 0;
            }
        }

        // Format the result
        $result = [
            'total' => self::formatAndCalculateCost($totalSymbols),
            'groups' => [],
        ];

        foreach ($countPerType as $type => $symbols) {
            $friendlyName = static::$fieldMap[$type] ?? $type;
            $formattedGroup = self::formatAndCalculateCost($symbols);

            $group = [
                'name' => $friendlyName,
                'symbols' => $formattedGroup['symbols'],
                'costs' => $formattedGroup['cost'],
                'locales' => [],
            ];

            foreach (array_keys($availableLocales) as $locale) {
                $key = $type . '_' . $locale;
                $localeSymbols = Arr::get($totalPerTypeAndLocale, $key, 0);
                $formattedLocale = self::formatAndCalculateCost($localeSymbols);

                $group['locales'][] = [
                    'name' => $availableLocales[$locale] ?? null,
                    'symbols' => $formattedLocale['symbols'],
                    'costs' => $formattedLocale['cost'],
                ];
            }

            $result['groups'][] = $group;
        }

        return $result;
    }

    /**
     * @return int
     */
    public static function pricePerMillionSymbols(): int
    {
        return intval(Config::get('translation-service.price_per_mil'));
    }

    /**
     * @return int
     */
    public static function maxMonthlyLimit(): int
    {
        return intval(Config::get('translation-service.max_monthly_limit'));
    }

    /**
     * Calculate the projected cost based on the number of symbols.
     *
     * @param int $symbols
     * @return array
     */
    private static function formatAndCalculateCost(int $symbols): array
    {
        return [
            'symbols' => $symbols,
            'cost' => self::calculateCost($symbols),
        ];
    }

    /**
     * Calculate the cost based on the number of symbols and the price per million symbols.
     *
     * @param int $symbols
     * @return string
     */
    private static function calculateCost(int $symbols): string
    {
        $pricePerMil = self::pricePerMillionSymbols();
        $cost = ($symbols / 1_000_000) * $pricePerMil;

        return currency_format_locale($cost);
    }

    /**
     * Get the list of available translatable types.
     *
     * @return array
     */
    private static function getAvailableTranslatableTypes(): array
    {
        return ['fund', 'product', 'organization', 'implementation_page', 'implementation_block'];
    }

    /**
     * Get the list of available locales.
     *
     * @return array
     */
    private static function getAvailableLocales(): array
    {
        $locales = Language::query()
            ->where('base', '=', false)
            ->pluck('name', 'locale')
            ->toArray();

        return array_combine(
            array_replace_values(array_keys($locales), Config::get('translation-service.translations_map')),
            array_values($locales),
        );
    }

    /**
     * Get translatable type groups for combining types.
     *
     * @return array
     */
    private static function getTranslatableTypeGroups(): array
    {
        return [
            'fund_application' => [
                FundConfig::class, FundCriteriaStep::class, FundCriterion::class,
            ],
            'webshop_content' => [
                'faq', 'fund', 'implementation', 'implementation_block', 'implementation_page', Announcement::class,
            ],
            'providers_content' => [
                'organization', ReservationField::class, 'product', PreCheck::class, PreCheckRecord::class,
            ],
        ];
    }
}
