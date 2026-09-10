<?php

namespace App\Services\PdfToImgService\Implementations;

use App\Services\PdfToImgService\Contracts\PdfToImgConverterContract;
use App\Services\PdfToImgService\Data\PdfToImgPageData;
use App\Services\PdfToImgService\Data\PdfToImgRequestData;
use App\Services\PdfToImgService\Data\PdfToImgResponseData;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use Aws\Exception\AwsException;
use Aws\Lambda\LambdaClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class AwsLambdaPdfToImgConverter implements PdfToImgConverterContract
{
    /**
     * @param LambdaClient $client
     * @param string $functionName
     * @param string|null $qualifier
     * @param string $disk
     * @param string $bucket
     * @param string $inputPrefix
     * @param string $outputPrefix
     * @param bool $cleanup
     */
    public function __construct(
        protected LambdaClient $client,
        protected string $functionName,
        protected ?string $qualifier = null,
        protected string $disk = 's3_pdf_to_img',
        protected string $bucket = '',
        protected string $inputPrefix = 'pdf-to-img/local/input',
        protected string $outputPrefix = 'pdf-to-img/local/output',
        protected bool $cleanup = true,
    ) {
    }

    /**
     * @param PdfToImgRequestData $request
     * @throws PdfToImgException
     * @return PdfToImgResponseData
     */
    public function convert(PdfToImgRequestData $request): PdfToImgResponseData
    {
        $conversionId = $this->makeConversionId();
        $inputKey = $this->makeInputKey($conversionId);
        $outputPrefix = $this->makeOutputPrefix($conversionId);

        try {
            Storage::disk($this->disk)->put($inputKey, $request->getPdf());

            $result = $this->client->invoke(array_filter([
                'FunctionName' => $this->functionName,
                'InvocationType' => 'RequestResponse',
                'Payload' => json_encode($this->makePayload($request, $conversionId, $inputKey, $outputPrefix), JSON_THROW_ON_ERROR),
                'Qualifier' => $this->qualifier,
            ], fn ($value) => $value !== null));

            $payload = $this->payloadToString($result['Payload'] ?? '');

            if ($result['FunctionError'] ?? false) {
                throw new PdfToImgException($this->makeFunctionErrorMessage($payload, $result['FunctionError']));
            }

            $data = $this->decodeResponsePayload($payload);
            $this->assertResponsePayloadValid($data);

            return $this->makeResponseData(
                data: $data,
                pages: array_map(fn ($page) => $this->normalizeS3Page($page), $data['pages']),
            );
        } catch (Throwable $e) {
            throw $this->makeFailureException($e, $conversionId, $inputKey, $outputPrefix);
        } finally {
            $this->cleanup($inputKey, $outputPrefix);
        }
    }

    /**
     * @param PdfToImgRequestData $request
     * @param string $conversionId
     * @param string $inputKey
     * @param string $outputPrefix
     * @return array
     */
    protected function makePayload(
        PdfToImgRequestData $request,
        string $conversionId,
        string $inputKey,
        string $outputPrefix,
    ): array {
        return [
            'requestId' => $conversionId,
            'input' => [
                'bucket' => $this->bucket,
                'key' => $inputKey,
            ],
            'output' => [
                'bucket' => $this->bucket,
                'prefix' => $outputPrefix,
            ],
            'options' => $this->makeOptionsPayload($request),
        ];
    }

    /**
     * @param PdfToImgRequestData $request
     * @return array
     */
    protected function makeOptionsPayload(PdfToImgRequestData $request): array
    {
        return array_filter([
            'pages' => $request->getPages(),
            'maxPages' => $request->getMaxPages(),
            'dpi' => $request->getDpi(),
            'quality' => $request->getQuality(),
            'maxWidth' => $request->getMaxWidth(),
            'maxHeight' => $request->getMaxHeight(),
            'oversize' => $request->getOversize(),
            'strictPageValidation' => $request->getStrictPageValidation(),
        ], fn ($value) => $value !== null);
    }

    /**
     * @param mixed $page
     * @throws PdfToImgException
     * @return PdfToImgPageData
     */
    protected function normalizeS3Page(mixed $page): PdfToImgPageData
    {
        if (!is_array($page)) {
            throw new PdfToImgException('PDF to image converter returned an invalid page item.');
        }

        foreach (['page', 'contentType', 'width', 'height', 'key'] as $field) {
            if (!array_key_exists($field, $page)) {
                throw new PdfToImgException("PDF to image converter page response is missing [$field].");
            }
        }

        if (!is_int($page['page']) || !is_int($page['width']) || !is_int($page['height'])) {
            throw new PdfToImgException('PDF to image converter returned invalid page dimensions.');
        }

        if (!is_string($page['contentType'])) {
            throw new PdfToImgException('PDF to image converter returned an invalid page content type.');
        }

        if (!is_string($page['key']) || $page['key'] === '') {
            throw new PdfToImgException('PDF to image converter returned an invalid page storage key.');
        }

        try {
            $image = Storage::disk($this->disk)->get($page['key']);
        } catch (Throwable $e) {
            throw new PdfToImgException('Failed to read rendered PDF page image.', previous: $e);
        }

        if (!is_string($image) || $image === '') {
            throw new PdfToImgException('PDF to image converter returned invalid page image data.');
        }

        return new PdfToImgPageData(
            page: $page['page'],
            contentType: $page['contentType'],
            width: $page['width'],
            height: $page['height'],
            image: $image,
        );
    }

    /**
     * @param string $payload
     * @throws PdfToImgException
     * @return array
     */
    protected function decodeResponsePayload(string $payload): array
    {
        $data = json_decode($payload, true);

        if (!is_array($data)) {
            throw new PdfToImgException('PDF to image converter returned malformed JSON.');
        }

        if (array_key_exists('errorType', $data) || array_key_exists('errorMessage', $data)) {
            throw new PdfToImgException($this->makeErrorMessage($data));
        }

        if (($data['statusCode'] ?? 200) >= 400) {
            throw $this->makeErrorException($data);
        }

        return $data;
    }

    /**
     * @param array $data
     * @throws PdfToImgException
     * @return void
     */
    protected function assertResponsePayloadValid(array $data): void
    {
        foreach (['pageCount', 'renderedCount', 'dpi', 'quality', 'pages', 'warnings'] as $field) {
            if (!array_key_exists($field, $data)) {
                throw new PdfToImgException("PDF to image converter response is missing [$field].");
            }
        }

        foreach (['pageCount', 'renderedCount', 'dpi', 'quality'] as $field) {
            if (!is_int($data[$field])) {
                throw new PdfToImgException("PDF to image converter response field [$field] is invalid.");
            }
        }

        if (!is_array($data['pages'])) {
            throw new PdfToImgException('PDF to image converter response field [pages] is invalid.');
        }

        if (empty($data['pages'])) {
            throw new PdfToImgException('PDF to image converter returned no rendered pages.');
        }

        if ($data['renderedCount'] !== count($data['pages'])) {
            throw new PdfToImgException('PDF to image converter returned a rendered page count mismatch.');
        }

        if (!is_array($data['warnings']) || array_filter($data['warnings'], fn ($warning) => !is_string($warning))) {
            throw new PdfToImgException('PDF to image converter response field [warnings] is invalid.');
        }
    }

    /**
     * @param array $data
     * @param array $pages
     * @return PdfToImgResponseData
     */
    protected function makeResponseData(array $data, array $pages): PdfToImgResponseData
    {
        return new PdfToImgResponseData(
            pageCount: $data['pageCount'],
            renderedCount: $data['renderedCount'],
            dpi: $data['dpi'],
            quality: $data['quality'],
            pages: $pages,
            warnings: $data['warnings'],
        );
    }

    /**
     * @return string
     */
    protected function makeConversionId(): string
    {
        return strtolower((string) Str::ulid());
    }

    /**
     * @param string $conversionId
     * @return string
     */
    protected function makeInputKey(string $conversionId): string
    {
        return implode('/', [
            trim($this->inputPrefix, '/'),
            now()->format('Y/m/d'),
            $conversionId,
            'source.pdf',
        ]);
    }

    /**
     * @param string $conversionId
     * @return string
     */
    protected function makeOutputPrefix(string $conversionId): string
    {
        return implode('/', [
            trim($this->outputPrefix, '/'),
            now()->format('Y/m/d'),
            $conversionId,
        ]);
    }

    /**
     * @param string $inputKey
     * @param string $outputPrefix
     * @return void
     */
    protected function cleanup(string $inputKey, string $outputPrefix): void
    {
        if (!$this->cleanup) {
            return;
        }

        try {
            Storage::disk($this->disk)->delete($inputKey);
            Storage::disk($this->disk)->deleteDirectory($outputPrefix);
        } catch (Throwable $exception) {
            $this->logCleanupFailure($exception, $inputKey, $outputPrefix);
        }
    }

    /**
     * @param Throwable $exception
     * @param string $inputKey
     * @param string $outputPrefix
     * @return void
     */
    protected function logCleanupFailure(Throwable $exception, string $inputKey, string $outputPrefix): void
    {
        Log::channel(Config::string('forus.pdf_to_img.log_channel'))->warning(implode("\n", array_filter([
            'PDF to image scratch cleanup failed.',
            json_encode([
                'disk' => $this->disk,
                'input_key' => $inputKey,
                'output_prefix' => $outputPrefix,
            ], JSON_PRETTY_PRINT),
            $exception->getMessage(),
            $exception->getTraceAsString(),
        ])));
    }

    /**
     * @param string $payload
     * @param string $functionError
     * @return string
     */
    protected function makeFunctionErrorMessage(string $payload, string $functionError): string
    {
        $data = json_decode($payload, true);

        if (is_array($data)) {
            $message = $data['errorMessage'] ?? $data['message'] ?? null;

            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return "PDF to image Lambda function error [$functionError].";
    }

    /**
     * @param array $data
     * @return string
     */
    protected function makeErrorMessage(array $data): string
    {
        $errorType = $data['errorType'] ?? null;
        $error = $data['error'] ?? null;
        $message = is_array($error) ? ($error['message'] ?? null) : ($error ?? $data['errorMessage'] ?? null);
        $message ??= $data['message'] ?? null;

        $errorType = is_scalar($errorType) ? (string) $errorType : null;
        $message = is_scalar($message) ? (string) $message : null;

        if ($errorType && $message) {
            return "$errorType: $message";
        }

        return $message ?? $errorType ?? 'PDF to image converter returned an error.';
    }

    /**
     * @param array $data
     * @return PdfToImgException
     */
    protected function makeErrorException(array $data): PdfToImgException
    {
        $error = $data['error'] ?? null;
        $errorCode = is_array($error) && is_scalar($error['code'] ?? null) ? (string) $error['code'] : null;
        $errorParams = is_array($error) && is_array($error['params'] ?? null) ? $error['params'] : [];

        return new PdfToImgException(
            $this->makeErrorMessage($data),
            errorCode: $errorCode,
            errorParams: $errorParams,
        );
    }

    /**
     * @param Throwable $previous
     * @param string $conversionId
     * @param string $inputKey
     * @param string $outputPrefix
     * @return PdfToImgException
     */
    protected function makeFailureException(
        Throwable $previous,
        string $conversionId,
        string $inputKey,
        string $outputPrefix,
    ): PdfToImgException {
        $message = match (true) {
            $previous instanceof JsonException => 'Failed to encode PDF to image Lambda payload.',
            $previous instanceof AwsException => $previous->getAwsErrorMessage() ?: $previous->getMessage(),
            default => $previous->getMessage(),
        };
        $errorCode = $previous instanceof PdfToImgException ? $previous->getErrorCode() : null;
        $errorParams = $previous instanceof PdfToImgException ? $previous->getErrorParams() : [];

        return new PdfToImgException(implode(' ', array_filter([
            $message ?: 'PDF to image conversion failed.',
            sprintf(
                '[request_id=%s input_key=%s output_prefix=%s]',
                $conversionId,
                $inputKey,
                $outputPrefix,
            ),
        ])), previous: $previous, errorCode: $errorCode, errorParams: $errorParams);
    }

    /**
     * @param mixed $payload
     * @throws PdfToImgException
     * @return string
     */
    protected function payloadToString(mixed $payload): string
    {
        if (is_string($payload)) {
            return $payload;
        }

        if (is_object($payload) && method_exists($payload, 'getContents')) {
            return $payload->getContents();
        }

        throw new PdfToImgException('PDF to image Lambda returned an invalid payload.');
    }
}
