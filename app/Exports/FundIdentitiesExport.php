<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Model;

class FundIdentitiesExport extends BaseExport
{
    protected array $builderWithArray = [
        'primary_email',
    ];

    protected static string $transKey = 'fund_identities';

    /**
     * @var array|string[][]
     */
    protected static array $exportFields = [
        'id',
        'email',
        'count_vouchers',
        'count_vouchers_active',
        'count_vouchers_active_with_balance',
    ];

    /**
     * @param Model|Identity $model
     * @return array
     */
    protected function getRow(Model|Identity $model): array
    {
        return [
            'id' => $model->id,
            'email' => $model->email,
            'count_vouchers' => $model->getAttribute('count_vouchers'),
            'count_vouchers_active' => $model->getAttribute('count_vouchers_active'),
            'count_vouchers_active_with_balance' => $model->getAttribute('count_vouchers_active_with_balance'),
        ];
    }
}
