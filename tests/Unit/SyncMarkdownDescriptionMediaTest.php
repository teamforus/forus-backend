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
     */
    public function testSyncMarkdownDescriptionMedia(): void
    {
        $fund = Fund::first();
        $media1 = self::uploadMedia();
        $media2 = self::uploadMedia();
        $description = [
            '# lorem ipsum 1',
            '![]('. $media1->urlPublic('original') .')',
            '# lorem ipsum 2',
        ];

        $fund->description = implode('', $description);
        $fund->save();
        $fund->syncDescriptionMarkdownMedia('cms_media');

        $this->assertEquals($fund->id, $media1->refresh()->mediable_id);
        $this->assertEquals($fund->getMorphClass(), $media1->refresh()->mediable_type);

        $fund->description = implode('', array_merge($description, [
            '# lorem ipsum 3',
            '![]('. $media2->urlPublic('original') .')',
        ]));
        $fund->save();
        $fund->syncDescriptionMarkdownMedia('cms_media');

        $this->assertEquals($fund->id, $media2->refresh()->mediable_id);
        $this->assertEquals($fund->getMorphClass(), $media2->refresh()->mediable_type);

        $fund->description = implode('', $description);
        $fund->save();
        $fund->syncDescriptionMarkdownMedia('cms_media');

        $this->assertNull(Media::find($media2->id));
    }

    /**
     * @return Media
     */
    protected function uploadMedia(): Media
    {
        $fileName = 'media.jpg';
        $file = UploadedFile::fake()->image($fileName);

        return resolve('media')->uploadSingle(
            $file,
            $fileName,
            'cms_media'
        );
    }
}