<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Employees\IndexEmployeesRequest;
use App\Http\Resources\EmployeeResource;
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
        $employees = $request->identity()->employees();

        if ($role) {
            $employees->whereRelation('roles', 'key', $role);
        }

        return EmployeeResource::queryCollection($employees, $request->input('per_page', 20));
    }
}
