<?php

namespace App\Models\Traits;

use Carbon\Carbon;

/**
 * Trait NodeTrait
 * @property string|null $created_at_string
 * @property string|null $created_at_string_locale
 * @property string|null $updated_at_string
 * @property string|null $updated_at_string_locale
 * @package App\Models\Traits
 */
trait HasFormattedDatesTrait
{
    /**
     * @return string|null
     */
    public function getCreatedAtStringAttribute(): ?string {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null;
    }

    /**
     * @return string|null
     */
    public function getCreatedAtStringLocaleAttribute(): ?string {
        return $this->created_at ? format_date_locale($this->created_at) : null;
    }

    /**
     * @return string|null
     */
    public function getUpdatedAtStringAttribute(): ?string {
        return $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null;
    }

    /**
     * @return string|null
     */
    public function getUpdatedAtStringLocaleAttribute(): ?string {
        return $this->updated_at ? format_date_locale($this->updated_at) : null;
    }
}