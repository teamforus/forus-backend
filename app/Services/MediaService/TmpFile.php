<?php


namespace App\Services\MediaService;


/**
 * Class TmpFile
 * @package App\Services\MediaService
 */
class TmpFile
{
    private $resource = null;

    /**
     * TmpFile constructor.
     * @param string $content
     * @param bool $isTmpFilePath
     */
    public function __construct(string $content, $isTmpFilePath = false)
    {
        if ($isTmpFilePath) {
            $this->resource = fopen($content, 'rb');
        } else {
            $this->resource = tmpfile();
            fwrite($this->resource, $content);
        }
    }

    /**
     * @param string $path
     * @return TmpFile
     */
    public static function fromTmpFile(string $path): TmpFile
    {
        return new self($path, true);
    }

    /**
     * @param string $path
     * @return TmpFile
     */
    public static function fromFile(string $path): TmpFile
    {
        return new self(file_get_contents($path));
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