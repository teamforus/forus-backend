<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use \Illuminate\Database\Eloquent\Builder;
use DB;

/**
 * App\Models\FundProvider
 *
 * @property int $id
 * @property int $organization_id
 * @property int $fund_id
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundProvider whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundProvider extends Model
{
    const STATE_PENDING = 'pending';
    const STATE_APPROVED = 'approved';
    const STATE_DECLINED = 'declined';

    const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
    ];

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
     * @return Builder
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
                    ->orWhere('phone', 'like', "%{$q}%");
                    // TODO: Remove?
                    /*->orWhereHas('product_categories', function (
                        Builder $query
                    ) use($q) {
                        return $query->whereHas('translations', function (
                            Builder $query
                        ) use ($q) {
                            $query->where('name', 'LIKE', "%{$q}%");
                        });
                    });*/
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
            'organization'
        ])->get()->map(function(FundProvider $fundProvider) use ($transKey) {
            $organization = $fundProvider->organization;

            return [
                trans("$transKey.provider") => $organization->name,
                trans("$transKey.email") => $organization->email_public ?
                    $organization->email : '',
                trans("$transKey.phone") => $organization->phone ?
                    $organization->phone : '',
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
