<?php

namespace App\Services\DigIdService\Objects;

use Illuminate\Support\Arr;

abstract class DigidData
{
    protected array $meta = [];

    /**
     * @param string|null $key
     * @param $default
     * @return mixed
     */
    public function getMeta(string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->meta;
        }

        return Arr::get($this->meta, $key, $default);
    }

    /**
     * @param array $meta
     * @return self
     * @noinspection PhpUnused
     */
    public function setMeta(array $meta): self
    {
        return $this->tap(fn() => $this->meta = $meta);
    }

    /**
     * @param callable $callback
     * @return $this
     */
    protected function tap(callable $callback): self
    {
        $callback();
        return $this;
    }
}