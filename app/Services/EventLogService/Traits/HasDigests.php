<?php

namespace App\Services\EventLogService\Traits;

use App\Services\EventLogService\Models\Digest;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasDigests
 * @package App\Services\EventLogService\Traits
 * @mixin \Eloquent
 */
trait HasDigests
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function digests(): MorphMany
    {
        return $this->morphMany(Digest::class, 'digestable');
    }

    /**
     * @param string $type
     * @return Digest|null
     */
    public function lastDigestOfType(string $type): ?Digest
    {
        /** @var Digest|null $digest */
        $digest = $this->digests()->where([
            'type' => $type
        ])->orderByDesc('created_at')->first();

        return $digest;
    }
}