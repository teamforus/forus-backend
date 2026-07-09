<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;

trait UsesPdfFixtures
{
    /**
     * @param string $fixture
     * @return string
     */
    protected function pdfFixturePath(string $fixture = 'one-page.pdf'): string
    {
        return base_path("tests/assets/pdf/$fixture");
    }

    /**
     * @param string $fixture
     * @return string
     */
    protected function pdfFixtureContents(string $fixture = 'one-page.pdf'): string
    {
        return file_get_contents($this->pdfFixturePath($fixture));
    }

    /**
     * @param string $name
     * @param string $fixture
     * @return UploadedFile
     */
    protected function makePdfFixtureUpload(string $name = 'reservation.pdf', string $fixture = 'one-page.pdf'): UploadedFile
    {
        return new UploadedFile($this->pdfFixturePath($fixture), $name, 'application/pdf', null, true);
    }
}
