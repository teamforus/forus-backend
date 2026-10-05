<?php

namespace App\Services\WalletService\Models;

use App\Models\Implementation;
use App\Services\WalletService\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $provider
 * @property string $type
 * @property string $key
 * @property string $name
 * @property array|null $context
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection|\App\Models\Implementation[] $implementations
 * @method static Builder<static>|WalletFlow newModelQuery()
 * @method static Builder<static>|WalletFlow newQuery()
 * @method static Builder<static>|WalletFlow onlyTrashed()
 * @method static Builder<static>|WalletFlow query()
 * @method static Builder<static>|WalletFlow whereContext($value)
 * @method static Builder<static>|WalletFlow whereCreatedAt($value)
 * @method static Builder<static>|WalletFlow whereDeletedAt($value)
 * @method static Builder<static>|WalletFlow whereId($value)
 * @method static Builder<static>|WalletFlow whereKey($value)
 * @method static Builder<static>|WalletFlow whereName($value)
 * @method static Builder<static>|WalletFlow whereProvider($value)
 * @method static Builder<static>|WalletFlow whereUpdatedAt($value)
 * @method static Builder<static>|WalletFlow withTrashed()
 * @method static Builder<static>|WalletFlow withoutTrashed()
 * @mixin \Eloquent
 */
class WalletFlow extends Model
{
    use SoftDeletes;

    public const string TYPE_AUTHENTICATION = 'authentication';
    public const string TYPE_DISCLOSURE = 'disclosure';

    protected $attributes = ['type' => self::TYPE_AUTHENTICATION];

    protected $table = 'wallet_flows';

    protected $hidden = ['context'];

    /**
     * @var string[]
     */
    protected $fillable = [
        'provider', 'type', 'key', 'name', 'context',
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
            'implementation_wallet_flows',
            'wallet_flow_id',
            'implementation_id',
        )->withTimestamps();
    }

    /**
     * @return array|null
     */
    public function providerContext(): ?array
    {
        return is_array($this->context) ? $this->context : null;
    }

    /**
     * @return bool
     */
    public function configured(): bool
    {
        return WalletService::providerContextConfigured($this->provider, $this->providerContext(), $this->type);
    }

    /**
     * @param string $provider
     * @param string $type
     * @return Collection
     */
    public static function configuredForProvider(string $provider, string $type = self::TYPE_AUTHENTICATION): Collection
    {
        return new Collection(WalletFlow::query()
            ->where('provider', $provider)
            ->where('type', $type)
            ->get()
            ->filter(fn (WalletFlow $flow) => $flow->configured())
            ->values());
    }
}
