<?php

namespace App\Exports\Traits;

trait FormatsExportedData
{
    /**
     * @param string $key
     * @return string|null
     */
    protected function getFormat(string $key): ?string
    {
        foreach ($this->formats ?? [] as $format => $formatKeys) {
            if (in_array($key, $formatKeys)) {
                return $format;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        $keys = array_keys($this->data->first() ?: []);
        $alphabet = range('A', 'Z');

        return array_reduce($keys, function($list, string $key) use ($keys, $alphabet) {
            if ($format = $this->getFormat($key)) {
                return array_merge($list, [$alphabet[array_search($key, $keys)] => $format]);
            }

            return $list;
        }, []);
    }
}