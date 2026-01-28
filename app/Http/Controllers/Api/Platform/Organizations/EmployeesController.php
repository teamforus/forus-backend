<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Employees\EmployeeDeleted;
use App\Events\Employees\EmployeeUpdated;
use App\Exports\EmployeesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Employees\IndexEmployeesRequest;
use App\Http\Requests\Api\Platform\Organizations\Employees\StoreEmployeeRequest;
use App\Http\Requests\Api\Platform\Organizations\Employees\UpdateEmployeeRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
use App\Searches\EmployeesSearch;
use App\Traits\ThrottleWithMeta;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeesController extends Controller
{
    use ThrottleWithMeta;

    public function __construct()
    {
        $this->maxAttempts = env('EMPLOYEE_INVITE_THROTTLE_ATTEMPTS', 10);
        $this->decayMinutes = env('EMPLOYEE_INVITE_THROTTLE_DECAY', 10);
    }

    /**
     * Display a listing of the resource.
     *
     * @param IndexEmployeesRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexEmployeesRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('show', [$organization]);
        $this->authorize('viewAny', [Employee::class, $organization]);

        $search = new EmployeesSearch($request->only([
            'role', 'roles', 'permission', 'permissions', 'q',
        ]), $organization->employees()->getQuery());

        return EmployeeResource::queryCollection($search->query(), $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreEmployeeRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     * @return EmployeeResource
     */
    public function store(
        StoreEmployeeRequest $request,
        Organization $organization,
    ): EmployeeResource {
        $this->throttleWithKey('to_many_attempts', $request, 'invite_employee');

        $this->authorize('show', [$organization]);
        $this->authorize('store', [Employee::class, $organization]);

        $email = $request->post('email');
        $roles = $request->post('roles');
        $office_id = $request->post('office_id');
        $employee = $request->employee($organization);

        $identity = Identity::findOrBuild(
            email: $email,
            type: Identity::TYPE_EMPLOYEE,
            employeeId: $employee->id,
            organizationId: $organization->id,
        );

        return EmployeeResource::create($organization->addEmployee($identity, $roles, $office_id));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Employee $employee
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return EmployeeResource
     */
    public function show(Organization $organization, Employee $employee): EmployeeResource
    {
        $this->authorize('show', [$organization]);
        $this->authorize('show', [$employee, $organization]);

        return EmployeeResource::create($employee);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateEmployeeRequest $request
     * @param Organization $organization
     * @param Employee $employee
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return EmployeeResource
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

        return EmployeeResource::create($employee);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Employee $employee
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     * @return JsonResponse
     */
    public function destroy(Organization $organization, Employee $employee): JsonResponse
    {
        $this->authorize('show', [$organization]);
        $this->authorize('destroy', [$employee, $organization]);

        EmployeeDeleted::broadcast($employee);
        $employee->delete();

        return new JsonResponse([]);
    }

    /**
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('show', [$organization]);
        $this->authorize('viewAny', [Employee::class, $organization]);

        return ExportFieldArrResource::collection(EmployeesExport::getExportFields());
    }

    /**
     * @param IndexEmployeesRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return BinaryFileResponse
     */
    public function export(
        IndexEmployeesRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', [$organization]);
        $this->authorize('viewAny', [Employee::class, $organization]);

        $search = new EmployeesSearch($request->only([
            'role', 'roles', 'permission', 'permissions', 'q',
        ]), $organization->employees()->getQuery());

        $exportType = $request->input('data_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $exportType;
        $fields = $request->input('fields', EmployeesExport::getExportFieldsRaw());
        $exportData = new EmployeesExport($search->query(), $fields);

        return resolve('excel')->download($exportData, $fileName);
    }
}
