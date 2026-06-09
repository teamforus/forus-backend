<?php

namespace App\Services\OpenIdService\Models;

use App\Models\Implementation;
use App\Services\OpenIdService\OpenIdService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $provider
 * @property string $key
 * @property string $name
 * @property array|null $context
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection|\App\Models\Implementation[] $implementations
 * @method static Builder<static>|OpenIdFlow newModelQuery()
 * @method static Builder<static>|OpenIdFlow newQuery()
 * @method static Builder<static>|OpenIdFlow onlyTrashed()
 * @method static Builder<static>|OpenIdFlow query()
 * @method static Builder<static>|OpenIdFlow whereContext($value)
 * @method static Builder<static>|OpenIdFlow whereCreatedAt($value)
 * @method static Builder<static>|OpenIdFlow whereDeletedAt($value)
 * @method static Builder<static>|OpenIdFlow whereId($value)
 * @method static Builder<static>|OpenIdFlow whereKey($value)
 * @method static Builder<static>|OpenIdFlow whereName($value)
 * @method static Builder<static>|OpenIdFlow whereProvider($value)
 * @method static Builder<static>|OpenIdFlow whereUpdatedAt($value)
 * @method static Builder<static>|OpenIdFlow withTrashed()
 * @method static Builder<static>|OpenIdFlow withoutTrashed()
 * @mixin \Eloquent
 */
class OpenIdFlow extends Model
{
    use SoftDeletes;

    protected $table = 'openid_flows';

    /**
     * @var string[]
     */
    protected $fillable = [
        'provider',
        'key',
        'name',
        'context',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'context' => 'array',
    ];

    /**
     * @return BelongsToMany
     */
    public function implementations(): BelongsToMany
    {
        return $this->belongsToMany(
            Implementation::class,
            'implementation_openid_flows',
            'openid_flow_id',
            'implementation_id',
        )->withTimestamps();
    }

    /**
     * @return array|null
     */
    public function openidContext(): ?array
    {
        return is_array($this->context) ? $this->context : null;
    }

    /**
     * @return bool
     */
    public function configured(): bool
    {
        return OpenIdService::providerContextConfigured($this->provider, $this->openidContext());
    }

    /**
     * @param string $provider
     * @return Collection
     */
    public static function configuredForProvider(string $provider): Collection
    {
        return new Collection(OpenIdFlow::query()
            ->where('provider', $provider)
            ->get()
            ->filter(fn (OpenIdFlow $flow) => $flow->configured())
            ->values());
    }
}
