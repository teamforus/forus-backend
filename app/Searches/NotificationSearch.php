<?php

namespace App\Searches;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class NotificationSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder|Relation|Notification $builder
     */
    public function __construct(array $filters, Builder|Relation|Notification $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder|Relation|Notification
     */
    public function query(): Builder|Relation|Notification
    {
        /** @var Builder|Relation|Notification $builder */
        $builder = parent::query();

        if ($this->hasFilter('organization_id')) {
            $builder->where('organization_id', $this->getFilter('organization_id'));
        }

        if ($this->getFilter('seen') === true) {
            $builder->whereNotNull('read_at');
        } elseif ($this->getFilter('seen') === false) {
            $builder->whereNull('read_at');
        }

        return $builder;
    }
}
