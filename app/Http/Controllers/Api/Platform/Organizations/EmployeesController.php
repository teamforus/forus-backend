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
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @return EmployeeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function store(
        StoreEmployeeRequest $request,
        Organization $organization
    ): EmployeeResource {
        $this->throttleWithKey('to_many_attempts', $request, 'invite_employee');

        $this->authorize('show', [$organization]);
        $this->authorize('store', [Employee::class, $organization]);

        $email = $request->input('email');
        $roles = $request->input('roles');

        $identity = Identity::findByEmail($email) ?: Identity::make($email);
        $employee = $organization->addEmployee($identity, $roles);

        return EmployeeResource::create($employee);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Employee $employee
     * @return EmployeeResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Organization $organization, Employee $employee): EmployeeResource
    {
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
    ): EmployeeResource {
        $this->authorize('show', [$organization]);
        $this->authorize('update', [$employee, $organization]);

        $previousRoles = $employee->roles()->pluck('key');
        $employee->roles()->sync($request->input('roles', []));

        EmployeeUpdated::dispatch($employee, $previousRoles->toArray());

        return new EmployeeResource($employee);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param Employee $employee
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(Organization $organization, Employee $employee): JsonResponse
    {
        $this->authorize('show', [$organization]);
        $this->authorize('destroy', [$employee, $organization]);

        EmployeeDeleted::broadcast($employee);
        $employee->delete();

        return response()->json([]);
    }

    /**
     * @param IndexEmployeesRequest $request
     * @param Organization $organization
     * @return BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(
        IndexEmployeesRequest $request,
        Organization $organization
    ): BinaryFileResponse {
        $this->authorize('show', [$organization]);

        $exportType = $request->input('export_type', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.'. $exportType;

        $query = $organization->employees()->with([
            'roles',
            'logs' => fn (MorphMany $builder) => $builder->where(
                'event', Employee::EVENT_UPDATED
            )->latest()
        ]);

        $search = new EmployeesSearch($request->only([
            'role', 'roles', 'permission', 'permissions', 'q',
        ]), $query->getQuery());

        $exportData = new EmployeesExport($search->query()->get());

        return resolve('excel')->download($exportData, $fileName);
    }
}
