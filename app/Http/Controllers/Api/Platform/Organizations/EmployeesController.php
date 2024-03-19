<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Employees\EmployeeDeleted;
use App\Events\Employees\EmployeeUpdated;
use App\Exports\EmployeesExport;
use App\Http\Requests\Api\Platform\Organizations\Employees\IndexEmployeesRequest;
use App\Http\Requests\Api\Platform\Organizations\Employees\StoreEmployeeRequest;
use App\Http\Requests\Api\Platform\Organizations\Employees\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\Identity;
use App\Searches\EmployeesSearch;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeesController extends Controller
{
    use ThrottleWithMeta;

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateEmployeeRequest $request
     * @param Organization $organization
     * @param Employee $employee
     * @return EmployeeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateEmployeeRequest $request,
        Organization $organization,
        Employee $employee
    ): EmployeeResource {
        $this->authorize('show', [$organization]);
        $this->authorize('update', [$employee, $organization]);

        $previousRoles = $employee->roles()->pluck('key');

        if ($employee->identity_address != $organization->identity_address) {
            $employee->roles()->sync($request->input('roles', []));
        }

        $employee->update($request->only('office_id'));

        EmployeeUpdated::dispatch($employee, $previousRoles->toArray());

        return new EmployeeResource($employee);
    }
}
