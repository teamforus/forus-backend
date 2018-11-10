<?php

namespace App\Models;

class Model extends \Illuminate\Database\Eloquent\Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Append created_at/updated_at human readable attributes
        if ($this->timestamps) {
            $this->append('updated_at_locale');
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

        return format_date_locale($value, 'short_date_time_locale');
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

        return format_date_locale($value, 'short_date_time_locale');
    }
}