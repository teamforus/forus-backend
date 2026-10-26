<?php

namespace App\Services\DigIdService\Traits;

use Illuminate\Support\Arr;

trait BuildsSamlConfig
{
    /**
     * @param array $base
     * @param array $overrides
     * @param array $replace
     * @return array
     */
    protected function mergeSamlConfig(array $base, array $overrides, array $replace = []): array
    {
        $configs = array_replace_recursive($base, $overrides);

        foreach ($replace as $key => $value) {
            Arr::set($configs, $key, $value);
        }

        return $configs;
    }
}
