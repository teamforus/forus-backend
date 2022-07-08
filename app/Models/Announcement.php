<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Announcement
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property string $scope
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @method static Builder|Announcement newModelQuery()
 * @method static Builder|Announcement newQuery()
 * @method static Builder|Announcement query()
 * @method static Builder|Announcement whereActive($value)
 * @method static Builder|Announcement whereCreatedAt($value)
 * @method static Builder|Announcement whereDescription($value)
 * @method static Builder|Announcement whereExpireAt($value)
 * @method static Builder|Announcement whereId($value)
 * @method static Builder|Announcement whereScope($value)
 * @method static Builder|Announcement whereTitle($value)
 * @method static Builder|Announcement whereType($value)
 * @method static Builder|Announcement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Announcement extends Model
{
    use HasMarkdownDescription;

    /**
     * @var string[]
     */
    protected $fillable = [
        'type', 'title', 'description', 'expire_at', 'scope', 'active',
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

        return $query
            ->where('active', true)
            ->whereIn('scope', [$request->client_type(), 'dashboards'])
            ->where(function (Builder $builder) {
                $builder->whereNull('expire_at');
                $builder->orWhere('expire_at', '>', now());
            })
            ->orderByDesc('created_at');
    }
}
