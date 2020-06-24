<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Employees\EmployeeCreated;
use App\Events\Employees\EmployeeDeleted;
use App\Events\Employees\EmployeeUpdated;
use App\Http\Requests\Api\Platform\Organizations\Employees\IndexEmployeesRequest;
use App\Http\Requests\Api\Platform\Organizations\Employees\StoreEmployeeRequest;
use App\Http\Requests\Api\Platform\Organizations\Employees\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Implementation;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;

/**
 * Class EmployeesController
 * @property IRecordRepo $recordRepo
 * @property IIdentityRepo $identityRepo
 * @package App\Http\Controllers\Api\Platform\Organizations
 */
class EmployeesController extends Controller
{
    private $recordRepo;
    private $identityRepo;
    private $notificationRepo;

    public function __construct(
        IRecordRepo $recordRepo,
        IIdentityRepo $identityRepo
    ) {
        $this->recordRepo = $recordRepo;
        $this->identityRepo = $identityRepo;
        $this->notificationRepo = resolve('forus.services.notification');
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
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('viewAny', [Employee::class, $organization]);

        if ($request->has('role') && $role = $request->input('role')) {
            $query = $organization->employeesOfRoleQuery($role);
        } else {
            $query = $organization->employees();
        }

        return EmployeeResource::collection($query->paginate(
            $request->input('per_page', 10)
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreEmployeeRequest $request
     * @param Organization $organization
     * @return EmployeeResource|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function store(
        StoreEmployeeRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('store', [Employee::class, $organization]);

        $email = $request->input('email');
        $target = $request->input('target', null);
        $identity_address = $this->recordRepo->identityAddressByEmail($email);

        if (!$identity_address) {
            $identity_address = $this->identityRepo->makeByEmail($email);
            $identityProxy = $this->identityRepo->makeIdentityPoxy($identity_address);
            $clientType = client_type('general');

            $confirmationLink = sprintf(
                "%s/confirmation/email/%s?%s",
                rtrim(Implementation::active()['url_' . $clientType], '/'),
                $identityProxy['exchange_token'],
                http_build_query(compact('target'))
            );

            $this->notificationRepo->sendEmailEmployeeAdded(
                $email,
                Implementation::emailFrom(),
                $organization->name,
                $confirmationLink
            );
        }

        /** @var Employee $employee */
        $employee = $organization->employees()->firstOrCreate([
            'identity_address' => $identity_address
        ]);

        $employee->roles()->sync($request->input('roles'));

        EmployeeCreated::dispatch($employee);

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
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        Employee $employee
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('destroy', [$employee, $organization]);

        EmployeeDeleted::broadcast($employee);

        $employee->delete();
    }
}
