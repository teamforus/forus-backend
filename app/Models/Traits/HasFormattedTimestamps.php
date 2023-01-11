<?php

namespace App\Models\Traits;

/**
 * @property string|null $created_at_string
 * @property string|null $created_at_string_locale
 * @property string|null $updated_at_string
 * @property string|null $updated_at_string_locale
 * @mixin \Eloquent
 */
trait HasFormattedTimestamps
{
    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getCreatedAtStringAttribute(): ?string
    {
        return $this->created_at?->format('Y-m-d H:i:s');
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getCreatedAtStringLocaleAttribute(): ?string
    {
        return $this->created_at ? format_datetime_locale($this->created_at) : null;
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getUpdatedAtStringAttribute(): ?string
    {
        return $this->updated_at?->format('Y-m-d H:i:s');
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getUpdatedAtStringLocaleAttribute(): ?string
    {
        return $this->updated_at ? format_datetime_locale($this->updated_at) : null;
    }
}