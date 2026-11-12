<?php

namespace App\Modules\VoucherPass\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Modules\VoucherPass\Models\VoucherPass;
use Illuminate\Http\Request;

/**
 * @property-read VoucherPass $resource
 */
class VoucherPassResource extends BaseJsonResource
{
    public const array LOAD = [
        'owner_identity.record_bsn', 'target_identity.record_bsn', 'explicit_funds', 'employee.identity.primary_email',
    ];

    public const array LOAD_NESTED = ['types' => VoucherPassTypeResource::class];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $pass = $this->resource;

        return [
            ...$pass->only([
                'id', 'organization_id', 'external_id', 'owner_identity_id', 'target_identity_id',
                'code', 'print_label', 'state', 'print_requested', 'employee_id',
            ]),
            'owner_bsn' => $pass->owner_identity->bsn,
            'target_bsn' => $pass->target_identity->bsn,
            'employee_email' => $pass->employee?->identity?->email,
            'types' => VoucherPassTypeResource::collection($pass->types),
            'fund_ids' => $pass->explicit_funds->modelKeys(),
            ...$this->makeTimestamps($pass->only([
                'created_at', 'updated_at',
            ])),
        ];
    }
}
