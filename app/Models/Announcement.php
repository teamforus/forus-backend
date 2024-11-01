<?php

namespace App\Models;

use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Announcement
 *
 * @property int $id
 * @property string $type
 * @property string|null $key
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property \Illuminate\Support\Carbon|null $start_at
 * @property string|null $scope
 * @property bool $active
 * @property int|null $implementation_id
 * @property int|null $role_id
 * @property int|null $organization_id
 * @property string|null $announceable_type
 * @property int|null $announceable_id
 * @property bool $dismissible
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent|null $announceable
 * @property-read string $description_html
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\Role|null $role
 * @method static Builder|Announcement newModelQuery()
 * @method static Builder|Announcement newQuery()
 * @method static Builder|Announcement onlyTrashed()
 * @method static Builder|Announcement query()
 * @method static Builder|Announcement whereActive($value)
 * @method static Builder|Announcement whereAnnounceableId($value)
 * @method static Builder|Announcement whereAnnounceableType($value)
 * @method static Builder|Announcement whereCreatedAt($value)
 * @method static Builder|Announcement whereDeletedAt($value)
 * @method static Builder|Announcement whereDescription($value)
 * @method static Builder|Announcement whereDismissible($value)
 * @method static Builder|Announcement whereExpireAt($value)
 * @method static Builder|Announcement whereId($value)
 * @method static Builder|Announcement whereImplementationId($value)
 * @method static Builder|Announcement whereKey($value)
 * @method static Builder|Announcement whereOrganizationId($value)
 * @method static Builder|Announcement whereRoleId($value)
 * @method static Builder|Announcement whereScope($value)
 * @method static Builder|Announcement whereStartAt($value)
 * @method static Builder|Announcement whereTitle($value)
 * @method static Builder|Announcement whereType($value)
 * @method static Builder|Announcement whereUpdatedAt($value)
 * @method static Builder|Announcement withTrashed()
 * @method static Builder|Announcement withoutTrashed()
 * @mixin \Eloquent
 */
class Announcement extends Model
{
    use HasMarkdownDescription, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'key', 'type', 'title', 'description', 'expire_at', 'scope', 'active', 'implementation_id',
        'announceable_type', 'announceable_id', 'dismissible', 'role_id', 'organization_id',
        'start_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'active' => 'boolean',
        'start_at' => 'datetime',
        'expire_at' => 'datetime',
        'dismissible' => 'boolean',
    ];

    /**
     * @return MorphTo
     * @noinspection PhpUnused
     */
    public function announceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
