<?php

namespace Tests\Unit;

use App\Models\Fund;
use App\Models\Organization;
use App\Services\MediaService\Models\Media;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\CreatesApplication;
use Tests\TestCase;
use Throwable;

class SyncMarkdownDescriptionMediaTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication;

    /**
     * @return void
     * @throws Throwable
     */
    public function testSyncMarkdownDescriptionMedia(): void
    {
        $fund = Fund::first();
        $media1 = self::uploadMedia('cms_media');
        $media2 = self::uploadMedia('cms_media');

        $description1 = implode("  \n", [
            '# Title 1',
            '![]('. $media1->urlPublic('public') .')',
            '# Title 2',
        ]);

        $description2 = implode("  \n", [
            '# Title 3',
            '![]('. $media2->urlPublic('public') .')',
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
     * @return void
     * @throws Throwable
     */
    public function testCopyMarkdownDescriptionWithMediaSameModelSameType(): void
    {
        $this->cloneMarkdownMedia(Fund::find(1), Fund::find(2), 'cms_media', 'cms_media');
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testCopyMarkdownDescriptionWithMediaSameModelDifferentType(): void
    {
        // same model type and different media type
        $this->cloneMarkdownMedia(Fund::find(1), Fund::find(2), 'cms_media', 'product_photo');
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testCopyMarkdownDescriptionWithMediaDifferentModelSameType(): void
    {
        // different model type and same media type
        $this->cloneMarkdownMedia(Fund::find(1), Organization::find(1), 'cms_media', 'cms_media');
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testCopyMarkdownDescriptionWithMediaDifferentModelDifferentType(): void
    {
        // different model type and different media type
        $this->cloneMarkdownMedia(Fund::find(1), Organization::find(1), 'cms_media', 'product_photo');
    }

    /**
     * @param Fund|Organization $model1
     * @param Fund|Organization $model2
     * @param string $mediaType1
     * @param string $mediaType2
     * @return void
     * @throws Throwable
     */
    protected function cloneMarkdownMedia(
        Fund|Organization $model1,
        Fund|Organization $model2,
        string $mediaType1,
        string $mediaType2
    ): void {
        $startTime = now();
        $media1 = self::uploadMedia($mediaType1);

        $description = implode("  \n", [
            '# Title 1',
            '![]('. $media1->urlPublic('public') .')',
            '# Title 2',
        ]);

        // Add first media and assert that it's linked to the fund
        $model1->description = $description;
        $model1->save();
        $model1->syncDescriptionMarkdownMedia($mediaType1);
        $this->updateAndAssertMediaLinked($model1, $media1);

        // copy the description to a new entity and assert that the media is still
        // liked to the initial fund
        $model2->description = $description;
        $model2->save();
        $model2->syncDescriptionMarkdownMedia($mediaType2);
        $this->updateAndAssertMediaLinked($model1, $media1);

        $fund2Medias = $model2->getDescriptionMarkdownMediaQuery()->where('type', $mediaType2);

        // assert there is exactly one media in the description
        $this->assertTrue($fund2Medias->count() == 1, 'Media not found in the description.');

        $fund2NewMedias = $model2->medias()
            ->where('created_at', '>=', $startTime)
            ->whereIn('id', $fund2Medias->select('id'))
            ->where('type', $mediaType2);

        // assert that exactly one new media was created and linked to the second fund
        self::assertTrue($fund2NewMedias->count() == 1, "The media was not copied.");
    }

    /**
     * @param Fund $fund
     * @param Media $media
     * @return void
     */
    protected function updateAndAssertMediaLinked(Fund $fund, Media $media): void
    {
        $this->assertEquals($fund->id, $media->refresh()->mediable_id);
        $this->assertEquals($fund->getMorphClass(), $media->refresh()->mediable_type);
    }

    /**
     * @param string $mediaType
     * @return Media
     * @throws \Exception
     */
    protected function uploadMedia(string $mediaType): Media
    {
        $fileName = 'media.jpg';
        $file = UploadedFile::fake()->image($fileName);

        return resolve('media')->uploadSingle($file, $fileName, $mediaType);
    }
}