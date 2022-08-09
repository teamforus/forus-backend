<?php

namespace Tests\Feature;

use App\Models\IdentityProxy;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use App\Services\MediaService\Traits\UsesMediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use UsesMediaService, DatabaseTransactions, WithFaker;

    /**
     * @var string
     */
    protected string $apiMediaUrl = '/api/v1/medias';

    /**
     * @var array
     */
    protected array $resourceStructure = [
        'ext',
        'uid',
        'type',
        'is_dark',
        'original_name',
        'dominant_color',
        'identity_address',
        'sizes' => ['thumbnail'],
    ];

    /**
     * @return void
     */
    public function testStoreCmsMedia(): void
    {
        $this->storeMediaOfType('cms_media', 3);
    }

    /**
     * @return void
     */
    public function testStoreFundLogoMedia(): void
    {
        $this->storeMediaOfType('fund_logo', 3);
    }

    /**
     * @return void
     */
    public function testStoreImplementationBannerMedia(): void
    {
        $this->storeMediaOfType('implementation_banner', 4);
    }

    /**
     * @return void
     */
    public function testStoreImplementationBlockMedia(): void
    {
        $this->storeMediaOfType('implementation_block_media', 4);
    }

    /**
     * @return void
     */
    public function testStoreEmailLogoBlockMedia(): void
    {
        $this->storeMediaOfType('email_logo', 3);
    }

    /**
     * @return void
     */
    public function testStoreOfficePhotoMedia(): void
    {
        $this->storeMediaOfType('office_photo', 3);
    }

    /**
     * @return void
     */
    public function testStoreOrganizationLogoMedia(): void
    {
        $this->storeMediaOfType('organization_logo', 3);
    }

    /**
     * @return void
     */
    public function testStoreInvalidType(): void
    {
        $this
            ->storeMediaOfTypeUpload('non_existing')
            ->assertJsonValidationErrorFor('type');
    }

    /**
     * @return void
     */
    public function testStoreInvalidFile(): void
    {
        $file = UploadedFile::fake()->create('media.jpg', 128);

        $this
            ->storeMediaOfTypeUpload('fund_logo', true, $file)
            ->assertJsonValidationErrorFor('file');
    }

    /**
     * @return void
     */
    protected function testDeleteAsAuthorMedia(): void
    {
        $mediaAuthor = $this->makeIdentityProxy($this->makeIdentity());
        $media = $this->storeMediaOfType('fund_logo', 3, $mediaAuthor);
        $headers = $this->makeApiHeaders($mediaAuthor);

        // Delete as creator
        $this->delete("$this->apiMediaUrl/$media->uid", [], $headers)->assertSuccessful();

        // Check if deleted
        Storage::disk('public')->assertMissing($media->presets->pluck('path')->toArray());
        $media->presets->each(fn (MediaPreset $preset) => $this->assertTrue(!$preset->fileExists()));
    }

    /**
     * @return void
     */
    protected function testDeleteAsDifferentUserMedia(): void
    {
        $mediaAuthor = $this->makeIdentityProxy($this->makeIdentity());
        $media = $this->storeMediaOfType('fund_logo', 3, $mediaAuthor);
        $headers = $this->makeApiHeaders(true);

        // Delete as different user
        $this->refreshApplication();
        $this->delete("$this->apiMediaUrl/$media->uid", [], $headers)->assertForbidden();
    }

    /**
     * @return void
     */
    protected function testDeleteAsGuestMedia(): void
    {
        $mediaAuthor = $this->makeIdentityProxy($this->makeIdentity());
        $media = $this->storeMediaOfType('fund_logo', 3, $mediaAuthor);
        $headers = $this->makeApiHeaders();

        // Delete as guest
        $this->refreshApplication();
        $this->delete("$this->apiMediaUrl/$media->uid", [], $headers)->assertUnauthorized();
    }

    /**
     * @return void
     */
    public function testStoreMediaAsGuest(): void
    {
        $response = $this->post($this->apiMediaUrl, [
            'file' => UploadedFile::fake()->image('media.jpg'),
            'type' => 'cms_media',
        ], $this->makeApiHeaders());

        $response->assertUnauthorized();
    }

    /**
     * @param string $type
     * @param int $assertedPresetsCount
     * @param IdentityProxy|bool|null $authProxy
     * @return Media
     */
    protected function storeMediaOfType(
        string $type,
        int $assertedPresetsCount,
        IdentityProxy|bool $authProxy = true,
    ): Media {
        Storage::fake('public');
        $response = $this->storeMediaOfTypeUpload($type, $authProxy);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $media = $this->mediaService()->findByUid($response->json('data.uid'));

        $this->assertNotEmpty($media);
        $this->assertModelExists($media);
        $this->assertCount($assertedPresetsCount, $media->presets);

        Storage::disk('public')->assertExists($media->presets->pluck('path')->toArray());
        $media->presets->each(fn(MediaPreset $preset) => $this->assertTrue($preset->fileExists()));

        return $media;
    }

    /**
     * @param string $type
     * @param IdentityProxy|bool $authProxy
     * @param UploadedFile|null $file
     * @return TestResponse
     */
    protected function storeMediaOfTypeUpload(
        string $type,
        IdentityProxy|bool $authProxy = true,
        ?UploadedFile $file = null,
    ): TestResponse {
        return $this->post($this->apiMediaUrl, [
            'file' => $file ?: UploadedFile::fake()->image('media.jpg'),
            'type' => $type,
        ], $this->makeApiHeaders($authProxy));
    }
}
