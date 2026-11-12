<?php

namespace App\Modules\DCP\Http\Requests\DCPEventLogs;

use App\Http\Requests\BaseFormRequest;

class IndexDCPEventLogsRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(),
        ];
    }
}
