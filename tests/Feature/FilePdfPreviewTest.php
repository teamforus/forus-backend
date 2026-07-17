<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Random\RandomException;
use Tests\TestCase;
use Tests\Traits\MakesProductReservationPdfFiles;
use Throwable;

class FilePdfPreviewTest extends TestCase
{
    use MakesProductReservationPdfFiles;
    use DatabaseTransactions;

    /**
     * @throws RandomException
     * @throws Throwable
     * @return void
     */
    public function testPdfResourceHidesPreviewPageMedia(): void
    {
        $this->fakeProductReservationPdfStorage();

        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFile($identity, 'reservation.pdf');

        $this->attachPdfPreviewPages($file, ['page-1.jpg']);

        $this
            ->apiGetFileRequest($identity, $file)
            ->assertSuccessful()
            ->assertJsonMissingPath('data.url')
            ->assertJsonPath('data.preview', null)
            ->assertJsonPath('data.uses_pdf_preview', true)
            ->assertJsonPath('data.has_pdf_preview_pages', true)
            ->assertJsonMissingPath('data.pdf_preview_pages');
    }

    /**
     * @throws RandomException
     * @return void
     */
    public function testPdfResourceReportsMissingPreviewPages(): void
    {
        $identity = $this->makeIdentity();
        $file = $this->makeProductReservationCustomFieldFile($identity, 'reservation.pdf');

        $this
            ->apiGetFileRequest($identity, $file)
            ->assertSuccessful()
            ->assertJsonPath('data.preview', null)
            ->assertJsonPath('data.uses_pdf_preview', true)
            ->assertJsonPath('data.has_pdf_preview_pages', false)
            ->assertJsonMissingPath('data.pdf_preview_pages')
            ->assertJsonMissingPath('data.url');
    }
}
