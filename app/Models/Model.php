<?php

namespace App\Models;

/**
 * App\Models\Model
 *
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model query()
 * @mixin \Eloquent
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Append created_at/updated_at human readable attributes
        if ($this->created_at) {
            $this->append('updated_at_locale');
        }

        if ($this->updated_at) {
            $this->append('created_at_locale');
        }
    }

    /**
     * Create localized version of created_at attribute
     *
     * @return string|null
     */
    public function getCreatedAtLocaleAttribute() {
        $value = $this->getAttribute('created_at');

        if (is_null($value)) {
            return $value;
        }

        return format_datetime_locale($value, 'short_date_time_locale');
    }

    /**
     * Create localized version version of updated_at attribute
     *
     * @return string|null
     */
    public function getUpdatedAtLocaleAttribute() {
        $value = $this->getAttribute('updated_at');

        if (is_null($value)) {
            return $value;
        }

        return format_datetime_locale($value, 'short_date_time_locale');
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return $this
     */
    public function updateModel(array $attributes = [], array $options = [])
    {
        return tap($this)->update($attributes, $options);
    }
}