<?php

namespace Tests\Feature\Cms;

use App\Helpers\Arr;
use App\Models\ImplementationPage;
use Exception;
use Tests\Feature\Cms\Concerns\InteractsWithImplementationPages;
use Throwable;

class ImplementationPageLegacyContentTest extends ImplementationCmsTestCase
{
    use InteractsWithImplementationPages;

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncImplementationPageBlocks(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $response = $this->postJson(
            $this->getUrlPages($implementation),
            $this->makePageData(),
            $this->makeApiHeaders($proxy),
        )->assertSuccessful();

        $this->assertSame([], $response->json('data.cms_blocks'));
        $this->assertNotEmpty($response->json('data.blocks'));

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $implementationPageUrl = $this->getUrlPages($implementation, $implementationPage);
        $implementationPageBlocks = $implementationPage->blocks()->select($this->blockKeys())->get();
        $blocksData = [
            ...$implementationPageBlocks->toArray(),
            $this->makePageBlockData(),
        ];

        $pageBody = [
            'blocks' => $blocksData,
            'external' => $implementationPage->external,
            'external_url' => $implementationPage->external_url,
        ];

        $this->patchJson($implementationPageUrl, $pageBody, $this->makeApiHeaders($proxy));
        $this->assertImplementationPageSaved($implementationPage->id, $pageBody);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testDestroyLegacyImplementationBlockMediaKeepsPreviousAuthorization(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $page = $this->makeImplementationPage($implementation, ImplementationPage::TYPE_HOME);
        $media = $this->makeMedia('implementation_block_media');
        $block = $page->blocks()->create([
            'title' => 'Legacy block',
            'description' => 'Legacy block description',
            'button_enabled' => false,
            'button_target_blank' => false,
            'button_link_label' => '',
            'order' => 0,
        ]);

        $media->forceFill(['identity_address' => $organization->identity_address])->save();
        $block->attachMediaByUid($media->uid);

        $this->deleteJson("$this->apiMediaUrl/$media->uid", [], $this->makeApiHeaders($proxy))->assertSuccessful();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSyncImplementationPageFaq(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);

        $pageType = Arr::where(ImplementationPage::PAGE_TYPES, fn ($type) => $type['faq']);

        $implementation->pages()->where(['page_type' => array_values($pageType)[0]['key']])->delete();

        $pageBody = $this->makePageData(['page_type' => array_values($pageType)[0]['key']]);
        $response = $this->postJson($this->getUrlPages($implementation), $pageBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $implementationPageUrl = $this->getUrlPages($implementation, $implementationPage);

        $updateBody = [
            'faq' => array_map(fn () => $this->makeFAQData(), range(1, 3)),
            'external' => $implementationPage->external,
            'external_url' => $implementationPage->external_url,
        ];

        $response = $this->patchJson($implementationPageUrl, $updateBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $this->assertImplementationPageSaved($response->json('data.id'), $updateBody);

        $updateBody['faq'] = $implementationPage->faq()
            ->select(['id', 'title', 'type', 'description'])
            ->get()
            ->reverse()
            ->values()
            ->toArray();
        $faqIds = array_column($updateBody['faq'], 'id');

        $response = $this->patchJson($implementationPageUrl, $updateBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $this->assertSame($faqIds, array_column($response->json('data.faq'), 'id'));
        $this->assertImplementationPageSaved($response->json('data.id'), $updateBody);
    }
}
