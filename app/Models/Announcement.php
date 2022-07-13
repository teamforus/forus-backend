<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Announcement
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property string|null $scope
 * @property bool $active
 * @property int|null $implementation_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @method static Builder|Announcement newModelQuery()
 * @method static Builder|Announcement newQuery()
 * @method static \Illuminate\Database\Query\Builder|Announcement onlyTrashed()
 * @method static Builder|Announcement query()
 * @method static Builder|Announcement whereActive($value)
 * @method static Builder|Announcement whereCreatedAt($value)
 * @method static Builder|Announcement whereDeletedAt($value)
 * @method static Builder|Announcement whereDescription($value)
 * @method static Builder|Announcement whereExpireAt($value)
 * @method static Builder|Announcement whereId($value)
 * @method static Builder|Announcement whereImplementationId($value)
 * @method static Builder|Announcement whereScope($value)
 * @method static Builder|Announcement whereTitle($value)
 * @method static Builder|Announcement whereType($value)
 * @method static Builder|Announcement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Announcement withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Announcement withoutTrashed()
 * @mixin \Eloquent
 */
class Announcement extends Model
{
    use HasMarkdownDescription, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'type', 'title', 'description', 'expire_at', 'scope', 'active', 'implementation_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'expire_at',
    ];

    /**
     * @param BaseFormRequest $request
     * @param null $query
     * @return Builder
     */
    public static function search(BaseFormRequest $request, $query = null): Builder
    {
        $query = $query ?: static::query();
        $clientType = $request->client_type();
        $implementation = $request->implementation();

        if ($clientType !== $implementation::FRONTEND_WEBSHOP) {
            $clientType = [$clientType, 'dashboards'];
        }

        return $query
            ->where(function(Builder $builder) use ($clientType, $implementation) {
                if ($clientType === $implementation::FRONTEND_WEBSHOP) {
                    $builder->whereNull('implementation_id');
                    $builder->orWhere('implementation_id', $implementation->id);
                }
            })
            ->where('active', true)
            ->whereIn('scope', (array) $clientType)
            ->where(function (Builder $builder) {
                $builder->whereNull('expire_at');
                $builder->orWhere('expire_at', '>=', now()->startOfDay());
            })
            ->orderByDesc('created_at');
    }
}
