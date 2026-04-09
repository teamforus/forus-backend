<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\PhysicalCardRequest;
use Illuminate\Database\Eloquent\Model;

class PhysicalCardRequestsExport extends BaseExport
{
    protected static string $transKey = 'physical_card_requests';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'address',
        'house',
        'house_addition',
        'postcode',
        'city',
    ];

    /**
     * @param Model|PhysicalCardRequest $model
     * @return array
     */
    protected function getRow(Model|PhysicalCardRequest $model): array
    {
        return $model->only([
            'address',
            'house',
            'house_addition',
            'postcode',
            'city',
        ]);
    }
}
