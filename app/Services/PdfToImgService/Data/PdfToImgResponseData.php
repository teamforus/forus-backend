<?php

namespace App\Services\PdfToImgService\Data;

class PdfToImgResponseData
{
    /**
     * @param PdfToImgPageData[] $pages
     * @param string[] $warnings
     */
    public function __construct(
        protected int $pageCount,
        protected int $renderedCount,
        protected int $dpi,
        protected int $quality,
        protected array $pages,
        protected array $warnings = [],
    ) {
    }

    /**
     * @return int
     */
    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    /**
     * @return int
     */
    public function getRenderedCount(): int
    {
        return $this->renderedCount;
    }

    /**
     * @return int
     */
    public function getDpi(): int
    {
        return $this->dpi;
    }

    /**
     * @return int
     */
    public function getQuality(): int
    {
        return $this->quality;
    }

    /**
     * @return PdfToImgPageData[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
