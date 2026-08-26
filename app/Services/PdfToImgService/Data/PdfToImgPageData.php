<?php

namespace App\Services\PdfToImgService\Data;

class PdfToImgPageData
{
    /**
     * @param int $page
     * @param string $contentType
     * @param int $width
     * @param int $height
     * @param string $image
     */
    public function __construct(
        protected int $page,
        protected string $contentType,
        protected int $width,
        protected int $height,
        protected string $image,
    ) {
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }
}
