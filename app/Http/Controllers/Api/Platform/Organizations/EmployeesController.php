<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Employees\StoreEmployeeRequest;
use App\Http\Requests\Api\Platform\Organizations\Employees\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class EmployeesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('index', [Employee::class, $organization]);

        return EmployeeResource::collection($organization->employees);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreEmployeeRequest $request
     * @param Organization $organization
     * @return EmployeeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreEmployeeRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('store', [Employee::class, $organization]);

        $identity_address = resolve(
            'forus.services.record'
        )->identityIdByEmail($request->input('email'));

        if ($organization->employees()->where([
                'identity_address' => $identity_address
            ])->count() > 0) {
            return response([
                "errors" => [
                    "email" => [trans("validation.employee_already_exists", [
                        'attribute' => 'email'
                    ])]
                ]
            ], 422);
        }

        /** @var Employee $employee */
        $employee = $organization->employees()->firstOrCreate([
            'identity_address' => $identity_address
        ]);

        $employee->roles()->sync($request->input(['roles']));

        return new EmployeeResource($employee);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Employee $employee
     * @return EmployeeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Employee $employee
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('show', [$employee, $organization]);

        return new EmployeeResource($employee);
    }

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
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('update', [$employee, $organization]);

        $employee->roles()->sync($request->input('roles', []));

        return new EmployeeResource($employee);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Employee $employee
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        Employee $employee
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('destroy', [$employee, $organization]);

        $employee->delete();
    }
}
