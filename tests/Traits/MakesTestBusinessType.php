<?php

namespace Tests\Traits;

use App\Models\BusinessType;
use Illuminate\Support\Str;

trait MakesTestBusinessType
{
    /**
     * @param string $name
     * @param array $data
     * @return BusinessType
     */
    protected function makeTestBusinessType(string $name, array $data = []): BusinessType
    {
        $type = BusinessType::create([
            'key' => Str::slug($name),
            ...$data,
        ]);

        $type->translateOrNew(app()->getLocale())->fill([
            'name' => $name,
        ])->save();

        return $type;
    }
}
