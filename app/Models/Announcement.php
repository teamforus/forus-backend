<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Announcement
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $expire_at
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
        'expire_at' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * @return string
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return resolve('markdown.converter')->convert($this->description ?: '')->getContent();
    }

    /**
     * @param BaseFormRequest $request
     * @param null $query
     * @return Builder
     */
    public static function search(BaseFormRequest $request, $query = null): Builder
    {
        $query = $query ?: self::query();
        $scope = $request->client_type();

        $query->where('scope', $scope);

        $query->where('active', true)
            ->where('expire_at', '>', now());

        return $query->orderByDesc('created_at');
    }

}
