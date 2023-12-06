<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

abstract class BaseFieldedExport implements FromCollection, WithHeadings
{
    protected static array $exportFields = [];
    protected static string $translationKey = '';
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
        $headings = array_unique(
            $this->data->reduce(fn($list, $row) => array_merge($list, array_keys($row)), [])
        );

        return array_map(static function($key) use ($headings) {
            if (!static::$translationKey) {
                return $key;
            }

            return trans("export.". static::$translationKey .".$key");
        }, (array) $headings);
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

    /**
     * @return array
     */
    public static function getExportFieldsRaw() : array
    {
        return static::$exportFields;
    }
}