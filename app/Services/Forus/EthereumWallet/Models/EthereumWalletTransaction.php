<?php

namespace App\Services\Forus\EthereumWallet\Models;

use App\Models\Traits\EloquentModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EthereumWalletTransaction
 * @property mixed              $id
 * @property string             $hash
 * @property string             $block_hash
 * @property int                $gas
 * @property int                $wallet_from_id
 * @property int                $wallet_to_id
 * @property float              $amount
 * @property string             $block_number
 * @property EthereumWallet     $wallet_from
 * @property EthereumWallet     $wallet_to
 * @property Carbon             $created_at
 * @property Carbon             $updated_at
 * @package App\Models
 */
class EthereumWalletTransaction extends Model
{
    use EloquentModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hash', 'block_hash', 'block_number', 'amount', 'gas',
        'wallet_from_id', 'wallet_to_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wallet_from() {
        return $this->belongsTo(EthereumWallet::class, 'wallet_from_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function wallet_to() {
        return $this->belongsTo(EthereumWallet::class, 'wallet_to_id');
    }
}
