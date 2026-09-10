<?php

namespace Tests\Fakes;

use App\Services\PdfToImgService\Contracts\PdfToImgConverterContract;
use App\Services\PdfToImgService\Data\PdfToImgPageData;
use App\Services\PdfToImgService\Data\PdfToImgRequestData;
use App\Services\PdfToImgService\Data\PdfToImgResponseData;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use Illuminate\Http\UploadedFile;

class FakePdfToImgConverter implements PdfToImgConverterContract
{
    protected PdfToImgResponseData $response;
    protected ?PdfToImgException $exception = null;
    protected array $requests = [];

    /**
     * @param PdfToImgResponseData|null $response
     */
    public function __construct(?PdfToImgResponseData $response = null)
    {
        if (!$response) {
            $page = new PdfToImgPageData(1, 'image/jpeg', 800, 1100, $this->makeDefaultJpeg());
            $response = new PdfToImgResponseData(1, 1, 150, 85, [$page]);
        }

        $this->response = $response;
    }

    /**
     * @param PdfToImgRequestData $request
     * @throws PdfToImgException
     * @return PdfToImgResponseData
     */
    public function convert(PdfToImgRequestData $request): PdfToImgResponseData
    {
        $this->requests[] = $request;

        if ($this->exception) {
            throw $this->exception;
        }

        return $this->response;
    }

    /**
     * @param PdfToImgResponseData $response
     * @return self
     */
    public function setResponse(PdfToImgResponseData $response): self
    {
        $this->response = $response;
        $this->exception = null;

        return $this;
    }

    /**
     * @param PdfToImgException $exception
     * @return self
     */
    public function setException(PdfToImgException $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * @return PdfToImgRequestData[]
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * @return string
     */
    protected function makeDefaultJpeg(): string
    {
        $file = UploadedFile::fake()->image('page.jpg', 800, 1100);

        return file_get_contents($file->getRealPath());
    }
}
