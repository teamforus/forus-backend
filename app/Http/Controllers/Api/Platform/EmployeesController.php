<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Employees\IndexEmployeesRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeesController extends Controller
{
    /**
     * @param IndexEmployeesRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexEmployeesRequest $request): AnonymousResourceCollection
    {
        $role = $request->input('role');
        $employees = Employee::where('identity_address', $request->auth_address());

        if ($role) {
            $employees = $employees->whereHas('roles', function(
                Builder $builder
            ) use ($role) {
                $builder->where('key', $role);
            });
        }

        return EmployeeResource::collection($employees->paginate(
            $request->input('per_page', 20)
        ));
    }
}
