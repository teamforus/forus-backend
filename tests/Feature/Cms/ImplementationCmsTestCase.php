<?php

namespace Tests\Feature\Cms;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use App\Services\MediaService\Models\Media;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Uid\UuidV4;
use Tests\TestCase;
use Tests\Traits\MakesTestOrganizations;

abstract class ImplementationCmsTestCase extends TestCase
{
    use WithFaker;
    use MakesTestOrganizations;
    use DatabaseTransactions;

    protected string $apiUrl = '/api/v1/platform/organizations/%s/implementations/%s';

    protected string $apiMediaUrl = '/api/v1/medias';

    /**
     * @param string $mediaType
     * @throws Exception
     * @return Media
     */
    protected function makeMedia(string $mediaType): Media
    {
        $fileName = 'media.jpg';
        $file = UploadedFile::fake()->image($fileName);

        return resolve('media')->uploadSingle($file, $fileName, $mediaType);
    }

    /**
     * @param Organization $organization
     * @return Implementation
     */
    protected function makeTestImplementation(Organization $organization): Implementation
    {
        return $organization->implementations()->create([
            'key' => UuidV4::v4(),
            'name' => $this->faker()->text(10),
        ]);
    }

    /**
     * @param Implementation $implementation
     * @param string $pageType
     * @return ImplementationPage
     */
    protected function makeImplementationPage(Implementation $implementation, string $pageType): ImplementationPage
    {
        return $implementation->pages()->create([
            'page_type' => $pageType,
            'state' => ImplementationPage::STATE_PUBLIC,
            'external' => false,
            'description_position' => ImplementationPage::DESCRIPTION_POSITION_REPLACE,
            'description_alignment' => 'left',
            'blocks_per_row' => 3,
        ]);
    }

    /**
     * @param Implementation $implementation
     * @param ImplementationPage|null $implementationPage
     * @return string
     */
    protected function getUrlPages(
        Implementation $implementation,
        ?ImplementationPage $implementationPage = null
    ): string {
        return sprintf(
            $this->apiUrl . ($implementationPage ? '/pages/%s' : '/pages'),
            $implementation->organization_id,
            $implementation->id,
            $implementationPage?->id,
        );
    }
}
