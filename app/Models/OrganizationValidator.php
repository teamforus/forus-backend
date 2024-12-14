<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\OrganizationValidator
 *
 * @property int $id
 * @property int $organization_id
 * @property int $validator_organization_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationValidator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationValidator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationValidator query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationValidator whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationValidator whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationValidator whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationValidator whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationValidator whereValidatorOrganizationId($value)
 * @mixin \Eloquent
 */
class OrganizationValidator extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'organization_id', 'validator_organization_id',
    ];
}
