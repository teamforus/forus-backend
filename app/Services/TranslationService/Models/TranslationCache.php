<?php

namespace App\Services\TranslationService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TranslationCache extends Model
{
    protected $fillable = [
        'translatable_type', 'translatable_id', 'key', 'value', 'locale',
    ];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
