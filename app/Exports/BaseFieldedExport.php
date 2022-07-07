<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

abstract class BaseFieldedExport implements FromCollection, WithHeadings
{
    protected static array $exportFields = [];
    protected Collection $data;

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return array_unique($this->data->reduce(fn($list, $row) => array_merge($list, array_keys($row)), []));
    }

    /**
     * @return array
     */
    public static function getExportFields() : array
    {
        return array_reduce(array_keys(static::$exportFields), fn($list, $key) => array_merge($list, [[
            'key' => $key,
            'name' => static::$exportFields[$key],
        ]]), []);
    }
}