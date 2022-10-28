<?php

namespace App\Helpers;

class TmpFile
{
    private $resource;

    /**
     * TmpFile constructor.
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->resource = tmpfile();
        fwrite($this->resource, $content);
    }

    /**
     * @return string|null
     */
    public function path(): ?string
    {
        return stream_get_meta_data($this->resource)['uri'] ?? null;
    }

    /**
     * @return bool|null
     */
    public function close(): ?bool
    {
        return $this->resource ? fclose($this->resource) : null;
    }
}