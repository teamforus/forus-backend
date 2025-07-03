<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class FundIdentitiesExport extends BaseFieldedExport
{
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
     * FundsExport constructor.
     * @param EloquentCollection|Identity[] $identities
     */
    public function __construct(EloquentCollection|array $identities, protected array $fields)
    {
        $this->data = $this->export($identities);
    }

    /**
     * @param EloquentCollection|Identity[] $identities
     * @return Collection
     */
    protected function export(EloquentCollection|array $identities): Collection
    {
        return $this->exportTransform($identities->load([
            'primary_email',
        ]));
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys(
            $data->map(fn (Identity $identity) => array_only($this->getRow($identity), $this->fields))
        );
    }

    /**
     * @param Identity $identity
     * @return array
     */
    protected function getRow(Identity $identity): array
    {
        return [
            'id' => $identity->id,
            'email' => $identity->email,
            'count_vouchers' => $identity->getAttribute('count_vouchers'),
            'count_vouchers_active' => $identity->getAttribute('count_vouchers_active'),
            'count_vouchers_active_with_balance' => $identity->getAttribute('count_vouchers_active_with_balance'),
        ];
    }
}
