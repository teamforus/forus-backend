<?php

namespace Tests\Traits;

use App\Services\PdfToImgService\Contracts\PdfToImgConverterContract;
use App\Services\PdfToImgService\PdfToImgService;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\ExecutableFinder;

trait UsesLocalPoppler
{
    /**
     * @return void
     */
    protected function skipIfLocalPopplerUnavailable(): void
    {
        if (Config::get('forus.pdf_to_img.testing.skip_local_poppler_tests')) {
            $this->markTestSkipped('Local Poppler PDF tests are disabled.');
        }

        $finder = new ExecutableFinder();

        foreach ($this->localPopplerBinaries() as $binary) {
            if (!$finder->find($binary)) {
                $this->markTestSkipped("Local Poppler binary [$binary] is not available on this host.");
            }
        }
    }

    /**
     * @return void
     */
    protected function useLocalPdfToImgConverter(): void
    {
        Config::set('forus.pdf_to_img.enabled', true);
        Config::set('forus.pdf_to_img.default', 'local');

        $this->app->forgetInstance(PdfToImgConverterContract::class);
        $this->app->forgetInstance(PdfToImgService::class);
        $this->app->forgetInstance('pdf_to_img');
    }

    /**
     * @return string[]
     */
    protected function localPopplerBinaries(): array
    {
        return [
            Config::string('forus.pdf_to_img.connections.local.binaries.pdfinfo'),
            Config::string('forus.pdf_to_img.connections.local.binaries.pdftoppm'),
        ];
    }
}
