<?php

namespace App\Modules\RequesterImport\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Modules\RequesterImport\Models\RequesterImportRecord;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use Illuminate\Http\Request;

/**
 * @property-read RequesterImportRow $resource
 */
class RequesterImportRowResource extends BaseJsonResource
{
    public const array LOAD = ['records'];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'organization_id', 'bsn',
            ]),
            'records' => $this->resource->records->map(fn (RequesterImportRecord $record) => [
                ...$record->only([
                    'id', 'record_type_key', 'value',
                ]),
                ...$this->makeTimestamps([
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]),
            ]),
            ...$this->makeTimestamps([
                'created_at' => $this->resource->created_at,
                'updated_at' => $this->resource->updated_at,
            ]),
        ];
    }
}
