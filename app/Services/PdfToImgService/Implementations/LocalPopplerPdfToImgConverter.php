<?php

namespace App\Services\PdfToImgService\Implementations;

use App\Services\PdfToImgService\Contracts\PdfToImgConverterContract;
use App\Services\PdfToImgService\Data\PdfToImgPageData;
use App\Services\PdfToImgService\Data\PdfToImgRequestData;
use App\Services\PdfToImgService\Data\PdfToImgResponseData;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class LocalPopplerPdfToImgConverter implements PdfToImgConverterContract
{
    protected const int DEFAULT_DPI = 150;
    protected const int DEFAULT_QUALITY = 85;
    protected const int DEFAULT_MAX_DIMENSION = 3000;

    /**
     * @param string $disk
     * @param string $path
     * @param string $pdfinfoBinary
     * @param string $pdftoppmBinary
     * @param int $timeout
     */
    public function __construct(
        protected string $disk,
        protected string $path,
        protected string $pdfinfoBinary = 'pdfinfo',
        protected string $pdftoppmBinary = 'pdftoppm',
        protected int $timeout = 30,
    ) {
    }

    /**
     * @param PdfToImgRequestData $request
     * @throws PdfToImgException
     * @return PdfToImgResponseData
     */
    public function convert(PdfToImgRequestData $request): PdfToImgResponseData
    {
        $this->assertLocalDisk();

        $conversionId = strtolower((string) Str::ulid());
        $runPath = trim($this->path, '/') . "/$conversionId";
        $sourcePath = "$runPath/source.pdf";

        try {
            Storage::disk($this->disk)->put($sourcePath, $request->getPdf());

            $pdfPath = Storage::disk($this->disk)->path($sourcePath);
            $pageCount = $this->getPdfPageCount($pdfPath);

            if ($request->getMaxPages() && $pageCount > $request->getMaxPages()) {
                throw new PdfToImgException(
                    "PDF page count $pageCount exceeds maxPages {$request->getMaxPages()}.",
                    errorCode: PdfToImgException::ERROR_MAX_PAGES_EXCEEDED,
                    errorParams: ['pageCount' => $pageCount, 'maxPages' => $request->getMaxPages()],
                );
            }

            $selection = $this->resolveSelectedPages($request, $pageCount);
            $warnings = [];

            if ($selection['skipped']) {
                $warnings[] = 'Requested pages were not available and were skipped: ' .
                    implode(', ', $selection['skipped']) . '.';
            }

            $pages = [];

            foreach ($selection['pages'] as $page) {
                $size = $this->getPdfPageSize($pdfPath, $page);
                $plan = $this->makeRenderPlan($page, $size, $request);

                if ($plan['scaled']) {
                    if (($request->getOversize() ?: 'scale') === 'error') {
                        throw new PdfToImgException(
                            "Selected pages exceed the max render size of {$plan['maxWidth']}x{$plan['maxHeight']}: $page."
                        );
                    }

                    $warnings[] = implode(' ', [
                        "Page $page was scaled from {$plan['requestedWidth']}x{$plan['requestedHeight']}",
                        "to {$plan['predictedWidth']}x{$plan['predictedHeight']}",
                        "to fit within {$plan['maxWidth']}x{$plan['maxHeight']}.",
                    ]);
                }

                $pages[] = $this->renderPage($pdfPath, $runPath, $page, $plan['dpi'], $this->getQuality($request));
            }

            return new PdfToImgResponseData(
                pageCount: $pageCount,
                renderedCount: count($pages),
                dpi: $this->getDpi($request),
                quality: $this->getQuality($request),
                pages: $pages,
                warnings: $warnings,
            );
        } finally {
            Storage::disk($this->disk)->deleteDirectory($runPath);
        }
    }

    /**
     * @throws PdfToImgException
     * @return void
     */
    protected function assertLocalDisk(): void
    {
        if (Config::get("filesystems.disks.$this->disk.driver") !== 'local') {
            throw new PdfToImgException("Local PDF to image converter disk [$this->disk] must use the local driver.");
        }
    }

    /**
     * @param string $pdfPath
     * @throws PdfToImgException
     * @return int
     */
    protected function getPdfPageCount(string $pdfPath): int
    {
        $output = $this->runProcess([$this->pdfinfoBinary, $pdfPath]);

        if (!preg_match('/^Pages:\s+(\d+)/m', $output, $matches)) {
            throw new PdfToImgException('Unable to determine PDF page count.');
        }

        return (int) $matches[1];
    }

    /**
     * @param string $pdfPath
     * @param int $page
     * @throws PdfToImgException
     * @return array{widthPts: float, heightPts: float}
     */
    protected function getPdfPageSize(string $pdfPath, int $page): array
    {
        $output = $this->runProcess([
            $this->pdfinfoBinary,
            '-f',
            (string) $page,
            '-l',
            (string) $page,
            '-box',
            $pdfPath,
        ]);

        if (!preg_match('/^Page(?:\s+\d+)?\s+size:\s+([\d.]+)\s+x\s+([\d.]+)\s+pts\b/im', $output, $matches)) {
            throw new PdfToImgException("Unable to determine PDF page $page size.");
        }

        return [
            'widthPts' => (float) $matches[1],
            'heightPts' => (float) $matches[2],
        ];
    }

    /**
     * @param PdfToImgRequestData $request
     * @param int $pageCount
     * @throws PdfToImgException
     * @return array
     */
    protected function resolveSelectedPages(PdfToImgRequestData $request, int $pageCount): array
    {
        $pages = $this->parsePages($request->getPages(), $pageCount);
        $availablePages = array_values(array_filter($pages, fn (int $page) => $page <= $pageCount));
        $skippedPages = array_values(array_filter($pages, fn (int $page) => $page > $pageCount));

        if ($request->getStrictPageValidation() && $skippedPages) {
            throw new PdfToImgException("Requested pages must be between 1 and $pageCount.");
        }

        if (!$availablePages) {
            throw new PdfToImgException('PDF to image converter returned no rendered pages.');
        }

        return [
            'pages' => $availablePages,
            'skipped' => $skippedPages,
        ];
    }

    /**
     * @param string|null $selector
     * @param int $pageCount
     * @throws PdfToImgException
     * @return int[]
     */
    protected function parsePages(?string $selector, int $pageCount): array
    {
        $selector = preg_replace('/\s+/', '', (string) $selector);
        $invalidPagesMessage = 'pages must be a comma-separated list like "1,3-4,8".';

        if ($selector === '') {
            return range(1, $pageCount);
        }

        $pages = [];

        foreach (explode(',', $selector) as $token) {
            if ($token === '') {
                throw new PdfToImgException($invalidPagesMessage);
            }

            if (ctype_digit($token)) {
                if ((int) $token <= 0) {
                    throw new PdfToImgException($invalidPagesMessage);
                }

                $pages[] = (int) $token;

                continue;
            }

            if (!preg_match('/^(\d+)-(\d+)$/', $token, $matches)) {
                throw new PdfToImgException($invalidPagesMessage);
            }

            $start = (int) $matches[1];
            $end = (int) $matches[2];

            if ($start <= 0 || $end <= 0) {
                throw new PdfToImgException($invalidPagesMessage);
            }

            if ($start > $end) {
                throw new PdfToImgException('pages ranges must be in ascending order.');
            }

            foreach (range($start, $end) as $page) {
                $pages[] = $page;
            }
        }

        $pages = array_values(array_unique($pages));
        sort($pages);

        return $pages;
    }

    /**
     * @param int $page
     * @param array{widthPts: float, heightPts: float} $size
     * @param PdfToImgRequestData $request
     * @return array{
     *     page: int,
     *     dpi: int,
     *     scaled: bool,
     *     maxWidth: int,
     *     maxHeight: int,
     *     requestedWidth: int,
     *     requestedHeight: int,
     *     predictedWidth: int,
     *     predictedHeight: int
     * }
     */
    protected function makeRenderPlan(int $page, array $size, PdfToImgRequestData $request): array
    {
        $dpi = $this->getDpi($request);
        $maxWidth = $this->getMaxWidth($request);
        $maxHeight = $this->getMaxHeight($request);
        $requestedSize = $this->calculateRasterDimensions($size['widthPts'], $size['heightPts'], $dpi);
        $scaled = $requestedSize['width'] > $maxWidth || $requestedSize['height'] > $maxHeight;

        if (!$scaled) {
            return [
                'page' => $page,
                'dpi' => $dpi,
                'scaled' => false,
                'maxWidth' => $maxWidth,
                'maxHeight' => $maxHeight,
                'requestedWidth' => $requestedSize['width'],
                'requestedHeight' => $requestedSize['height'],
                'predictedWidth' => $requestedSize['width'],
                'predictedHeight' => $requestedSize['height'],
            ];
        }

        $effectiveDpi = max(1, (int) floor($dpi * min(
            $maxWidth / $requestedSize['width'],
            $maxHeight / $requestedSize['height'],
            1,
        )));
        $predictedSize = $this->calculateRasterDimensions($size['widthPts'], $size['heightPts'], $effectiveDpi);

        while ($effectiveDpi > 1 && ($predictedSize['width'] > $maxWidth || $predictedSize['height'] > $maxHeight)) {
            $effectiveDpi--;
            $predictedSize = $this->calculateRasterDimensions($size['widthPts'], $size['heightPts'], $effectiveDpi);
        }

        return [
            'page' => $page,
            'dpi' => $effectiveDpi,
            'scaled' => true,
            'maxWidth' => $maxWidth,
            'maxHeight' => $maxHeight,
            'requestedWidth' => $requestedSize['width'],
            'requestedHeight' => $requestedSize['height'],
            'predictedWidth' => $predictedSize['width'],
            'predictedHeight' => $predictedSize['height'],
        ];
    }

    /**
     * @param float $widthPts
     * @param float $heightPts
     * @param int $dpi
     * @return array{width: int, height: int}
     */
    protected function calculateRasterDimensions(float $widthPts, float $heightPts, int $dpi): array
    {
        return [
            'width' => (int) ceil(($widthPts / 72) * $dpi),
            'height' => (int) ceil(($heightPts / 72) * $dpi),
        ];
    }

    /**
     * @param string $pdfPath
     * @param string $runPath
     * @param int $page
     * @param int $dpi
     * @param int $quality
     * @throws PdfToImgException
     * @return PdfToImgPageData
     */
    protected function renderPage(string $pdfPath, string $runPath, int $page, int $dpi, int $quality): PdfToImgPageData
    {
        $relativePrefix = "$runPath/page-" . str_pad((string) $page, 4, '0', STR_PAD_LEFT);
        $absolutePrefix = Storage::disk($this->disk)->path($relativePrefix);

        $this->runProcess([
            $this->pdftoppmBinary,
            '-jpeg',
            '-singlefile',
            '-f',
            (string) $page,
            '-l',
            (string) $page,
            '-r',
            (string) $dpi,
            '-jpegopt',
            "quality=$quality",
            $pdfPath,
            $absolutePrefix,
        ]);

        $image = Storage::disk($this->disk)->get("$relativePrefix.jpg");
        $size = getimagesizefromstring($image);

        if (!$size || ($size['mime'] ?? null) !== 'image/jpeg') {
            throw new PdfToImgException('PDF to image converter returned invalid page image data.');
        }

        return new PdfToImgPageData(
            page: $page,
            contentType: 'image/jpeg',
            width: $size[0],
            height: $size[1],
            image: $image,
        );
    }

    /**
     * @param array $command
     * @throws PdfToImgException
     * @return string
     */
    protected function runProcess(array $command): string
    {
        try {
            $process = new Process($command);
            $process->setTimeout($this->timeout);
            $process->run();
        } catch (Throwable $e) {
            throw new PdfToImgException($this->makeCommandError($command[0], $e->getMessage()), previous: $e);
        }

        if (!$process->isSuccessful()) {
            throw new PdfToImgException($this->makeCommandError(
                $command[0],
                trim($process->getErrorOutput() . "\n" . $process->getOutput()),
            ));
        }

        return $process->getOutput();
    }

    /**
     * @param string $command
     * @param string $message
     * @return string
     */
    protected function makeCommandError(string $command, string $message): string
    {
        $message = trim($message);

        return $message ? "$command failed: $message" : "$command failed.";
    }

    /**
     * @param PdfToImgRequestData $request
     * @return int
     */
    protected function getDpi(PdfToImgRequestData $request): int
    {
        return $request->getDpi() ?: self::DEFAULT_DPI;
    }

    /**
     * @param PdfToImgRequestData $request
     * @return int
     */
    protected function getQuality(PdfToImgRequestData $request): int
    {
        return $request->getQuality() ?: self::DEFAULT_QUALITY;
    }

    /**
     * @param PdfToImgRequestData $request
     * @return int
     */
    protected function getMaxWidth(PdfToImgRequestData $request): int
    {
        return $request->getMaxWidth() ?: ($request->getMaxHeight() ?: self::DEFAULT_MAX_DIMENSION);
    }

    /**
     * @param PdfToImgRequestData $request
     * @return int
     */
    protected function getMaxHeight(PdfToImgRequestData $request): int
    {
        return $request->getMaxHeight() ?: ($request->getMaxWidth() ?: self::DEFAULT_MAX_DIMENSION);
    }
}
