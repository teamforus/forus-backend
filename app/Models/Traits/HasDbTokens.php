<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Eloquent
 * @noinspection PhpUnused
 */
trait HasDbTokens
{
    /**
     * @param string $column
     * @param int $block_length
     * @param int $block_count
     * @param Builder|null $builder
     * @return string
     * @noinspection PhpUnused
     */
    public static function makeUniqueToken(
        string $column,
        int $block_length,
        int $block_count = 1,
        Builder $builder = null
    ): string {
        do {
            $value = token_generator()->generate($block_length, $block_count);
        } while((clone ($builder ?: static::query()))->where($column, $value)->exists());

        return $value;
    }

    /**
     * @param callable $isUnique
     * @param int $block_length
     * @param int $block_count
     * @return string
     * @noinspection PhpUnused
     */
    public static function makeUniqueTokenCallback(
        callable $isUnique,
        int $block_length,
        int $block_count = 1,
    ): string {
        do {
            $value = token_generator()->generate($block_length, $block_count);
        } while(!$isUnique($value));

        return $value;
    }
}