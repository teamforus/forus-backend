<?php

namespace App\Modules\RequesterImport\Http\Requests\RequesterImportRows;

use App\Http\Requests\BaseFormRequest;

class IndexRequesterImportRowsRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => $this->qRule(),
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(),
        ];
    }
}
