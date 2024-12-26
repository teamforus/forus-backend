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
 * @method static Builder<static>|Announcement newModelQuery()
 * @method static Builder<static>|Announcement newQuery()
 * @method static Builder<static>|Announcement onlyTrashed()
 * @method static Builder<static>|Announcement query()
 * @method static Builder<static>|Announcement whereActive($value)
 * @method static Builder<static>|Announcement whereAnnounceableId($value)
 * @method static Builder<static>|Announcement whereAnnounceableType($value)
 * @method static Builder<static>|Announcement whereCreatedAt($value)
 * @method static Builder<static>|Announcement whereDeletedAt($value)
 * @method static Builder<static>|Announcement whereDescription($value)
 * @method static Builder<static>|Announcement whereDismissible($value)
 * @method static Builder<static>|Announcement whereExpireAt($value)
 * @method static Builder<static>|Announcement whereId($value)
 * @method static Builder<static>|Announcement whereImplementationId($value)
 * @method static Builder<static>|Announcement whereKey($value)
 * @method static Builder<static>|Announcement whereOrganizationId($value)
 * @method static Builder<static>|Announcement whereRoleId($value)
 * @method static Builder<static>|Announcement whereScope($value)
 * @method static Builder<static>|Announcement whereStartAt($value)
 * @method static Builder<static>|Announcement whereTitle($value)
 * @method static Builder<static>|Announcement whereType($value)
 * @method static Builder<static>|Announcement whereUpdatedAt($value)
 * @method static Builder<static>|Announcement withTrashed()
 * @method static Builder<static>|Announcement withoutTrashed()
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
