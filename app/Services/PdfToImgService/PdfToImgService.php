<?php

namespace App\Services\PdfToImgService;

use App\Services\PdfToImgService\Contracts\PdfToImgConverterContract;
use App\Services\PdfToImgService\Data\PdfToImgRequestData;
use App\Services\PdfToImgService\Data\PdfToImgResponseData;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

class PdfToImgService
{
    /**
     * @param PdfToImgConverterContract $converter
     */
    public function __construct(protected PdfToImgConverterContract $converter)
    {
    }

    /**
     * @param PdfToImgRequestData $request
     * @throws PdfToImgException
     * @return PdfToImgResponseData
     */
    public function convert(PdfToImgRequestData $request): PdfToImgResponseData
    {
        try {
            $request = $request->normalize();

            return $this->converter->convert($request);
        } catch (PdfToImgException $exception) {
            static::logError('PDF to image conversion failed.', $exception, $this->makeLogContext($request));

            throw $exception;
        }
    }

    /**
     * @param string $message
     * @param Throwable $exception
     * @param array $context
     * @return void
     */
    public static function logError(string $message, Throwable $exception, array $context = []): void
    {
        Log::channel(Config::string('forus.pdf_to_img.log_channel'))->error(implode("\n", array_filter([
            $message,
            $context ? json_encode($context, JSON_PRETTY_PRINT) : null,
            $exception->getMessage(),
            $exception->getTraceAsString(),
        ])));
    }

    /**
     * @param PdfToImgRequestData $request
     * @return array
     */
    protected function makeLogContext(PdfToImgRequestData $request): array
    {
        $connectionName = Config::get('forus.pdf_to_img.default');
        $connection = Config::get("forus.pdf_to_img.connections.$connectionName", []);

        return [
            'connection' => $connectionName,
            'driver' => $connection['driver'] ?? null,
            'function_name' => $connection['lambda']['function_name'] ?? null,
            'qualifier' => $connection['lambda']['qualifier'] ?? null,
            'storage_disk' => $connection['storage']['disk'] ?? null,
            'pages' => $request->getPages(),
            'max_pages' => $request->getMaxPages(),
            'dpi' => $request->getDpi(),
            'quality' => $request->getQuality(),
            'max_width' => $request->getMaxWidth(),
            'max_height' => $request->getMaxHeight(),
            'oversize' => $request->getOversize(),
            'strict_page_validation' => $request->getStrictPageValidation(),
        ];
    }
}
