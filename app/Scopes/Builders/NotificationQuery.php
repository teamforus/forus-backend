<?php

namespace App\Scopes\Builders;

use App\Models\Identity;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class NotificationQuery
{
    /**
     * @param Builder|Relation|Notification $builder
     * @param Identity $identity
     * @return Builder|Relation|Notification
     */
    public static function whereVisibleToIdentity(
        Builder|Relation|Notification $builder,
        Identity $identity,
    ): Builder|Relation|Notification {
        return $builder->where(function (Builder $builder) use ($identity) {
            $builder
                ->whereNull('organization_id')
                ->orWhereIn('organization_id', $identity->employees()->select('organization_id'));
        });
    }
}
