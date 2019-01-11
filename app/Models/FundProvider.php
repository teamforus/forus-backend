<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Http\Request;
use \Illuminate\Database\Eloquent\Builder;
use DB;

/**
 * Class FundProvider
 * @property mixed $id
 * @property string $state
 * @property int $fund_id
 * @property int $organization_id
 * @property Fund $fund
 * @property Organization $organization
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class FundProvider extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'fund_id', 'state'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function search(
        Request $request,
        Organization $organization
    ) {
        $q = $request->input('q', null);
        $state = $request->input('state', null);

        $providers = FundProvider::getModel()->whereIn(
            'fund_id',
            $organization->funds()->pluck('id')
        );

        if ($q) {
            $providers = $providers->whereHas('organization', function (Builder $query) use ($q) {
                return $query->where('name', 'like', "%{$q}%")
                    ->orWhere('kvk', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('product_categories', function (Builder $query) use($q){
                        return $query->whereTranslationLike('name', "%{$q}%");
                    });
            });
        }

        if($state && in_array($state, ['approved', 'declined', 'pending'])){
            $providers = $providers->where('state', $state);
        }

        $providers = $providers->orderBy(
            DB::raw('FIELD(state, "pending", "approved", "declined")')
        );

        return $providers;
    }
}
