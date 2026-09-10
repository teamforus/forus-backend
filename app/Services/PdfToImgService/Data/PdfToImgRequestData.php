<?php

namespace App\Services\PdfToImgService\Data;

use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use Illuminate\Support\Facades\Config;

class PdfToImgRequestData
{
    protected const int MAX_QUALITY = 100;
    protected const array OVERSIZE_MODES = ['scale', 'error'];

    /**
     * @param string $pdf
     * @param string|null $pages
     * @param int|null $maxPages
     * @param int|null $dpi
     * @param int|null $quality
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     * @param string|null $oversize
     * @param bool|null $strictPageValidation
     */
    public function __construct(
        protected string $pdf,
        protected ?string $pages = null,
        protected ?int $maxPages = null,
        protected ?int $dpi = null,
        protected ?int $quality = null,
        protected ?int $maxWidth = null,
        protected ?int $maxHeight = null,
        protected ?string $oversize = null,
        protected ?bool $strictPageValidation = null,
    ) {
    }

    /**
     * @return string
     */
    public function getPdf(): string
    {
        return $this->pdf;
    }

    /**
     * @return string|null
     */
    public function getPages(): ?string
    {
        return $this->pages;
    }

    /**
     * @return int|null
     */
    public function getMaxPages(): ?int
    {
        return $this->maxPages;
    }

    /**
     * @return int|null
     */
    public function getDpi(): ?int
    {
        return $this->dpi;
    }

    /**
     * @return int|null
     */
    public function getQuality(): ?int
    {
        return $this->quality;
    }

    /**
     * @return int|null
     */
    public function getMaxWidth(): ?int
    {
        return $this->maxWidth;
    }

    /**
     * @return int|null
     */
    public function getMaxHeight(): ?int
    {
        return $this->maxHeight;
    }

    /**
     * @return string|null
     */
    public function getOversize(): ?string
    {
        return $this->oversize;
    }

    /**
     * @return bool|null
     */
    public function getStrictPageValidation(): ?bool
    {
        return $this->strictPageValidation;
    }

    /**
     * @throws PdfToImgException
     * @return self
     */
    public function normalize(): self
    {
        foreach ([
            'maxPages' => $this->maxPages,
            'dpi' => $this->dpi,
            'quality' => $this->quality,
            'maxWidth' => $this->maxWidth,
            'maxHeight' => $this->maxHeight,
        ] as $field => $value) {
            $this->assertPositiveInteger($field, $value);
        }

        if ($this->quality !== null && $this->quality > self::MAX_QUALITY) {
            throw new PdfToImgException('quality must be less than or equal to 100.');
        }

        if ($this->oversize !== null && !in_array($this->oversize, self::OVERSIZE_MODES, true)) {
            throw new PdfToImgException('oversize must be either "scale" or "error".');
        }

        return new self(
            pdf: $this->pdf,
            pages: $this->pages,
            maxPages: $this->maxPages,
            dpi: $this->dpi,
            quality: $this->quality,
            maxWidth: $this->maxWidth ?? $this->maxHeight,
            maxHeight: $this->maxHeight ?? $this->maxWidth,
            oversize: $this->oversize,
            strictPageValidation: $this->strictPageValidation,
        );
    }

    /**
     * @param string $pdf
     * @param array $options
     * @throws PdfToImgException
     * @return self
     */
    public static function fromConfig(string $pdf, array $options = []): self
    {
        $defaults = Config::get('forus.pdf_to_img.defaults');

        return (new self(
            pdf: $pdf,
            pages: null,
            maxPages: $options['max_pages'] ?? $defaults['max_pages'] ?? null,
            dpi: $options['dpi'] ?? $defaults['dpi'] ?? null,
            quality: $options['quality'] ?? $defaults['quality'] ?? null,
            maxWidth: $options['max_width'] ?? $defaults['max_width'] ?? null,
            maxHeight: $options['max_height'] ?? $defaults['max_height'] ?? null,
            oversize: $options['oversize'] ?? $defaults['oversize'] ?? null,
            strictPageValidation: $options['strict_page_validation'] ?? $defaults['strict_page_validation'] ?? null,
        ))->normalize();
    }

    /**
     * @param string $field
     * @param int|null $value
     * @throws PdfToImgException
     * @return void
     */
    protected function assertPositiveInteger(string $field, ?int $value): void
    {
        if ($value !== null && $value <= 0) {
            throw new PdfToImgException("$field must be a positive integer.");
        }
    }
}
