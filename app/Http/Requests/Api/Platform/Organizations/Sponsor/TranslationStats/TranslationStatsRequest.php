<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\TranslationStats;

use App\Http\Requests\Api\Platform\Organizations\Transactions\BaseIndexTransactionsRequest;

class TranslationStatsRequest extends BaseIndexTransactionsRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'from' => 'nullable|string|date_format:Y-m-d',
            'to' => 'nullable|string|date_format:Y-m-d',
        ];
    }
}
