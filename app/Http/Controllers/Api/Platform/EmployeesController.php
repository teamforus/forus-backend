<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\Employees\IndexEmployeesRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class EmployeesController extends Controller
{
    /**
     * @param IndexEmployeesRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexEmployeesRequest $request)
    {
        $role = $request->input('role', null);
        $employees = Employee::where('identity_address', auth_address());

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
