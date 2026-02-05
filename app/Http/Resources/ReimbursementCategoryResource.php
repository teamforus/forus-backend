<?php

namespace App\Http\Resources;

use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Models\ReimbursementCategory;
use Illuminate\Http\Request;

/**
 * @property-read ReimbursementCategory $resource
 */
class ReimbursementCategoryResource extends BaseJsonResource
{
    public const array LOAD = [
    ];

    public const array LOAD_COUNT = [
        'reimbursements',
    ];

    public const array LOAD_NESTED = [
        'organization' => OrganizationTinyResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only([
            'id', 'name', 'reimbursements_count',
        ]), [
            'organization' => OrganizationTinyResource::create($this->resource->organization),
        ]);
    }
}
