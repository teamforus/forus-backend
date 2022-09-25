<?php

namespace Tests\Unit;

use App\Models\Fund;
use App\Services\MediaService\Models\Media;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\CreatesApplication;
use Tests\TestCase;

class SyncMarkdownDescriptionMediaTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication;

    /**
     * @return void
     * @throws \Exception
     */
    public function testSyncMarkdownDescriptionMedia(): void
    {
        $fund = Fund::first();
        $media1 = self::uploadMedia();
        $media2 = self::uploadMedia();

        $description1 = implode("  \n", [
            '# Title 1',
            '![]('. $media1->urlPublic('original') .')',
            '# Title 2',
        ]);

        $description2 = implode("  \n", [
            '# Title 3',
            '![]('. $media2->urlPublic('original') .')',
        ]);

        // Add first media and assert that it's linked to the fund
        $fund->description = $description1;
        $fund->save();
        $fund->syncDescriptionMarkdownMedia('cms_media');
        $this->assertEquals($fund->id, $media1->refresh()->mediable_id);
        $this->assertEquals($fund->getMorphClass(), $media1->refresh()->mediable_type);

        // Add second media and assert that both media are linked
        $fund->description = "$description1  \n$description2";
        $fund->save();
        $fund->syncDescriptionMarkdownMedia('cms_media');
        $this->assertEquals($fund->id, $media1->refresh()->mediable_id);
        $this->assertEquals($fund->getMorphClass(), $media1->refresh()->mediable_type);
        $this->assertEquals($fund->id, $media2->refresh()->mediable_id);
        $this->assertEquals($fund->getMorphClass(), $media2->refresh()->mediable_type);

        // Remove the second media and assert that the first one is linked and the second one removed.
        $fund->description = $description1;
        $fund->save();
        $fund->syncDescriptionMarkdownMedia('cms_media');

        $this->assertEquals($fund->id, $media1->refresh()->mediable_id);
        $this->assertEquals($fund->getMorphClass(), $media1->refresh()->mediable_type);
        $this->assertNull(Media::find($media2->id));
    }

    /**
     * @return Media
     * @throws \Exception
     */
    protected function uploadMedia(): Media
    {
        $fileName = 'media.jpg';
        $file = UploadedFile::fake()->image($fileName);

        return resolve('media')->uploadSingle($file, $fileName, 'cms_media');
    }
}