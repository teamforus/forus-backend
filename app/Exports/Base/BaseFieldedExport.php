<?php

namespace App\Exports\Base;

use App\Exports\Traits\FormatsExportedData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;

abstract class BaseFieldedExport implements FromCollection, WithHeadings, WithColumnFormatting
{
    use FormatsExportedData;

    protected Collection $data;
    protected array $fields = [];
    protected static string $transKey = '';
    protected static array $exportFields = [];

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
        $collection = $this->collection();

        return $collection->isNotEmpty()
            ? array_keys($collection->first())
            : array_map(fn ($key) => static::trans($key), $this->fields);
    }

    /**
     * @return array
     */
    public static function getExportFields(): array
    {
        return array_reduce(static::$exportFields, fn ($list, $key) => array_merge($list, [[
            'key' => $key,
            'name' => static::trans($key),
        ]]), []);
    }

    /**
     * @return array
     */
    public static function getExportFieldsRaw(): array
    {
        return static::$exportFields;
    }

    /**
     * @param string $key
     * @return string|null
     */
    protected static function trans(string $key): ?string
    {
        $transKey = static::$transKey;

        return trans("export.$transKey.$key");
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function transformKeys(Collection $data): Collection
    {
        $fieldLabels = array_pluck(static::getExportFields(), 'name', 'key');

        return $data->map(function ($item) use ($fieldLabels) {
            return array_reduce(array_keys($item), fn ($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $item[$key],
            ]), []);
        });
    }
}
