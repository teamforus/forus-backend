<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @property Office $resource
 */
class OfficeResource extends BaseJsonResource
{
    public const array LOAD = [
        'organization',
    ];

    public const array LOAD_NESTED = [
        'photo' => MediaResource::class,
        'organization' => OrganizationBasicResource::class,
        'schedules' => OfficeScheduleResource::class,
    ];

    public const array LOAD_COUNT = [
        'employees',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|null
     */
    public function toArray(Request $request): ?array
    {
        if ($this->resource === null) {
            return null;
        }

        $office = $this->resource;
        $organization = $office->organization;

        return array_merge($office->only([
            'id', 'organization_id', 'address', 'phone', 'lon', 'lat',
            'postcode', 'postcode_number', 'postcode_addition',
        ]), [
            ...$this->privateData(),
            'photo' => new MediaResource($office->photo),
            'organization' => new OrganizationBasicResource($organization),
            'schedule' => OfficeScheduleResource::collection($office->schedules),
        ]);
    }

    /**
     * @return array
     */
    protected function privateData(): array
    {
        $isProviderDashboard = BaseFormRequest::createFromGlobals()->isProviderDashboard();
        $canUpdate = Gate::allows('update', [$this->resource, $this->resource->organization]);

        return $isProviderDashboard && $canUpdate ? $this->resource->only([
            'branch_id', 'branch_name', 'branch_number', 'branch_full_name',
            'employees_count',
        ]) : [];
    }
}
