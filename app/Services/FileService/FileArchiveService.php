<?php

namespace App\Services\FileService;

use App\Services\FileService\Models\File;
use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use League\Flysystem\UnableToReadFile;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use ZipArchive;

class FileArchiveService
{
    /**
     * @param File $file
     * @throws Throwable
     * @return string
     */
    public function makeArchive(File $file): string
    {
        $pdf = $this->getPdfContent($file);
        $pages = $this->getPreviewPages($file);

        $warning = $pages->isEmpty()
            ? trans('files.pdf_archive.warning_without_preview_pages')
            : trans('files.pdf_archive.warning');

        return $this->makeZip('file-archive-', function (ZipArchive $zip) use ($file, $pdf, $warning, $pages): void {
            $this->addToArchive($zip, "original-$file->uid.pdf", $pdf);
            $this->addToArchive($zip, 'warning.txt', $warning . PHP_EOL);
            $this->addPreviewPages($zip, $pages);
        });
    }

    /**
     * @param File $file
     * @throws Throwable
     * @return string
     */
    public function makePreviewArchive(File $file): string
    {
        $pages = $this->getPreviewPages($file);

        if ($pages->isEmpty()) {
            throw new NotFoundHttpException();
        }

        return $this->makeZip(
            'file-preview-archive-',
            fn (ZipArchive $zip) => $this->addPreviewPages($zip, $pages),
        );
    }

    /**
     * @param File $file
     * @throws NotFoundHttpException
     * @return string
     */
    protected function getPdfContent(File $file): string
    {
        try {
            $pdf = resolve('file')->getContent($file->path);
        } catch (UnableToReadFile) {
            throw new NotFoundHttpException();
        }

        if ($pdf === null) {
            throw new NotFoundHttpException();
        }

        return $pdf;
    }

    /**
     * @param File $file
     * @return Collection<array-key, Media>
     */
    protected function getPreviewPages(File $file): Collection
    {
        return $file->pdf_preview_pages()->with('presets')->get();
    }

    /**
     * @param string $prefix
     * @param callable $callback
     * @throws Throwable
     * @return string
     */
    protected function makeZip(string $prefix, callable $callback): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);

        if (!$path) {
            throw new RuntimeException('Could not create temporary file archive.');
        }

        $zip = new ZipArchive();
        $isOpen = false;

        try {
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create file archive.');
            }

            $isOpen = true;
            $callback($zip);
            $zip->close();

            return $path;
        } catch (Throwable $exception) {
            if ($isOpen) {
                $zip->close();
            }

            @unlink($path);

            throw $exception;
        }
    }

    /**
     * @param ZipArchive $zip
     * @param Collection<array-key, Media> $pages
     * @throws NotFoundHttpException
     * @return void
     */
    protected function addPreviewPages(ZipArchive $zip, Collection $pages): void
    {
        foreach ($pages as $index => $page) {
            $this->addToArchive($zip, sprintf('pages/%d.jpg', $index + 1), $this->getPageContent($page));
        }
    }

    /**
     * @param ZipArchive $zip
     * @param string $path
     * @param string $content
     * @return void
     */
    protected function addToArchive(ZipArchive $zip, string $path, string $content): void
    {
        if (!$zip->addFromString($path, $content)) {
            throw new RuntimeException("Could not add [$path] to file archive.");
        }
    }

    /**
     * @param Media $page
     * @throws NotFoundHttpException
     * @return string
     */
    protected function getPageContent(Media $page): string
    {
        $content = $page->getContent('original');

        if ($content === null) {
            throw new NotFoundHttpException();
        }

        return $content;
    }
}
