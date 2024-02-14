<?php

namespace App\Exports;

use App\Models\Identity;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class FundIdentitiesExport extends BaseFieldedExport
{
    use Exportable, RegistersEventListeners;

    protected Collection $data;
    protected array $fields;

    /**
     * @var array|\string[][]
     */
    protected static array $exportFields = [
        'id' => 'ID',
        'email' => 'E-mail',
        'count_vouchers' => 'Totaal aantal vouchers',
        'count_vouchers_active' => 'Actieve vouchers',
        'count_vouchers_active_with_balance' => 'Actieve vouchers met saldo',
    ];

    /**
     * FundsExport constructor.
     * @param EloquentCollection|Identity[] $identities
     */
    public function __construct(EloquentCollection|array $identities, array $fields = [])
    {
        $this->data = $identities->load('primary_email');
        $this->fields = $fields;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $data = $this->data->map(function(Identity $identity) {
            return array_only([
                'id' => $identity->id,
                'email' => $identity->email,
                'count_vouchers' => $identity->getAttribute('count_vouchers'),
                'count_vouchers_active' => $identity->getAttribute('count_vouchers_active'),
                'count_vouchers_active_with_balance' => $identity->getAttribute('count_vouchers_active_with_balance'),
            ], $this->fields);
        });

        return $data->map(function($item) {
            return array_reduce(array_keys($item), fn($obj, $key) => array_merge($obj, [
                static::$exportFields[$key] => (string) $item[$key],
            ]), []);
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->collection()->first());
    }
}