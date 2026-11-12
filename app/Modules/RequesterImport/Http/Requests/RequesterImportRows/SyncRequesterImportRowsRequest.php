<?php

namespace App\Modules\RequesterImport\Http\Requests\RequesterImportRows;

use App\Http\Requests\BaseFormRequest;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Services\RequesterImportProfileSyncService;
use Illuminate\Support\Facades\Gate;

class SyncRequesterImportRowsRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('sync', [RequesterImportRow::class, $this->getRouteOrganization()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'update_all' => 'sometimes|boolean',
            'row_ids' => ['sometimes', 'array', 'list', 'max:' . RequesterImportProfileSyncService::BATCH_SIZE],
            'row_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
