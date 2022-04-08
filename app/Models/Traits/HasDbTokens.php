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
        return token_generator_db($builder ?: static::query(), $column, $block_length, $block_count);
    }
}