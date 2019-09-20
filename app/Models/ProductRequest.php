<?php

namespace App\Models;

use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Record\Models\Record;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class ValidatorRequest
 * @property mixed $id
 * @property int $product_id
 * @property int $fund_id
 * @property string $identity_address
 * @property Product $product
 * @property Fund $fund
 * @property Carbon $resolved_at
 * @property Collection|ValidatorRequest[] $validator_requests
 * @package App\Models
 */
class ProductRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id', 'fund_id',
    ];

    protected $dates = [
        'resolved_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validator_requests() {
        return $this->hasMany(ValidatorRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }
}
