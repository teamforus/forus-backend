<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PlatformConfigUploadExtensionsTest extends TestCase
{
    /**
     * @return void
     */
    public function testPlatformConfigExposesUploadExtensions(): void
    {
        Config::set('forus.pdf_to_img.enabled', false);

        $this->getJson('/api/v1/platform/config/webshop')
            ->assertSuccessful()
            ->assertJsonPath('media.product_photo.source_extensions', ['jpg', 'jpeg', 'png', 'gif', 'bmp'])
            ->assertJsonPath('files.product_reservation_custom_field.source_extensions', ['jpg', 'jpeg', 'png'])
            ->assertJsonPath('files.reimbursement_proof.source_extensions', ['jpg', 'jpeg', 'png', 'pdf']);

        Config::set('forus.pdf_to_img.enabled', true);

        $this->getJson('/api/v1/platform/config/webshop')
            ->assertSuccessful()
            ->assertJsonPath(
                'files.product_reservation_custom_field.source_extensions',
                ['jpg', 'jpeg', 'png', 'pdf'],
            );
    }
}
