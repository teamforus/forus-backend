<?php

namespace App\Searches;

use App\Models\Employee;
use App\Scopes\Builders\EmployeeQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class EmployeesSearch extends BaseSearch
{
    /**
     * @param array $filters
     * @param Relation|Builder|Employee $builder
     */
    public function __construct(array $filters, Relation|Builder|Employee $builder)
    {
        parent::__construct($filters, $builder);
    }

    /**
     * @return Relation|Builder|Employee
     */
    public function query(): Relation|Builder|Employee
    {
        /** @var Relation|Builder|Employee $builder */
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
