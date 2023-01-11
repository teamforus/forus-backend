<?php

namespace App\Searches;

use App\Scopes\Builders\EmployeeQuery;
use Illuminate\Database\Eloquent\Builder;

class EmployeesSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Builder $builder
     */
    public function __construct(array $filters, Builder $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Builder
     */
    public function query(): Builder
    {
        /** @var Builder $builder */
        $builder = parent::query();

        $roleFilters = array_only($this->getFilters(), ['role', 'roles']);
        $permissionFilters = array_only($this->getFilters(), ['permission', 'permissions']);

        foreach ($roleFilters as $roleFilter) {
            EmployeeQuery::whereHasRoleFilter($builder, $roleFilter);
        }

        foreach ($permissionFilters as $permissionFilter) {
            EmployeeQuery::whereHasPermissionFilter($builder, $permissionFilter);
        }

        if ($q = $this->getFilter('q')) {
            EmployeeQuery::whereQueryFilter($builder, $q);
        }

        return $builder;
    }
}