<?php

namespace App\Services\Forus\Identity\Models;

use App\Models\Traits\EloquentModel;
use App\Services\Forus\EthereumWallet\Traits\HasEthereumWallet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Identity
 * @property mixed $id
 * @property string $pin_code
 * @property Collection $types
 * @property Collection $proxies
 * @property string $address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Identity extends Model
{
    use EloquentModel, HasEthereumWallet;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pin_code', 'address', 'passphrase', 'private_key', 'public_key'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proxies() {
        return $this->hasMany(IdentityProxy::class, 'identity_address', 'address');
    }

    /**
     * @param string $address
     * @return self
     */
    public function findByAddress(string $address) {
        return self::where(compact('address'))->first();
    }
}
