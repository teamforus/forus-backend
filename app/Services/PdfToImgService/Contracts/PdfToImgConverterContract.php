<?php

namespace App\Services\PdfToImgService\Contracts;

use App\Services\PdfToImgService\Data\PdfToImgRequestData;
use App\Services\PdfToImgService\Data\PdfToImgResponseData;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;

interface PdfToImgConverterContract
{
    /**
     * @throws PdfToImgException
     */
    public function convert(PdfToImgRequestData $request): PdfToImgResponseData;
}
