<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
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
        $fund_id = $request->input('fund_id', null);

        $providers = FundProvider::query()->whereIn(
            'fund_id',
            $organization->funds()->pluck('id')
        );

        if ($q) {
            $providers = $providers->whereHas('organization', function (
                Builder $query
            ) use ($q) {
                return $query->where('name', 'like', "%{$q}%")
                    ->orWhere('kvk', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('product_categories', function (
                        Builder $query
                    ) use($q) {
                        return $query->whereHas('translations', function (
                            Builder $query
                        ) use ($q) {
                            $query->where('name', 'LIKE', "%{$q}%");
                        });
                    });
            });
        }

        if ($fund_id) {
            $providers->where('fund_id', $fund_id);
        }

        if ($state && in_array($state, ['approved', 'declined', 'pending'])){
            $providers = $providers->where('state', $state);
        }

        $providers = $providers->orderBy(
            DB::raw('FIELD(state, "pending", "approved", "declined")')
        );

        return $providers;
    }

    /**
     * @param Builder $builder
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(Builder $builder) {
        $transKey = "export.providers";

        return $builder->with([
            'organization', 'organization.product_categories'
        ])->get()->map(function(
            FundProvider $fundProvider
        ) use ($transKey) {
            $organization = $fundProvider->organization;

            return [
                trans("$transKey.provider") => $organization->name,
                trans("$transKey.email") => $organization->email_public ?
                    $organization->email : '',
                trans("$transKey.phone") => $organization->phone ?
                    $organization->phone : '',
                trans("$transKey.categories") =>
                    $organization->product_categories->pluck(
                        'name'
                    )->implode(', '),
                trans("$transKey.kvk") => $fundProvider->organization->kvk,
                trans("$transKey.state") => trans(
                    "$transKey.state_values." . $fundProvider->state
                ),
            ];
        })->values();
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function export(
        Request $request,
        Organization $organization
    ) {
        return self::exportTransform(self::search($request, $organization));
    }
}
