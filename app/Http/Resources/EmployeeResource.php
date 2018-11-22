<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Resources\Json\Resource;

class EmployeeResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Employee$employee */
        $employee = $this->resource;

        $recordRepo = app()->make('forus.services.record');

        return collect($employee)->only([
            'id', 'identity_address', 'organization_id'
        ])->merge([
            'roles' => RoleResource::collection($employee->roles),
            'email' => $recordRepo->primaryEmailByAddress(
                $employee->identity_address
            ),
        ]);
    }
}
