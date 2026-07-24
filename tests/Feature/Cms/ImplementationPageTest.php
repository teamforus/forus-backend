<?php

namespace Tests\Feature\Cms;

use App\Helpers\Arr;
use App\Models\ImplementationPage;
use Tests\Feature\Cms\Concerns\InteractsWithImplementationPages;
use Throwable;

class ImplementationPageTest extends ImplementationCmsTestCase
{
    use InteractsWithImplementationPages;

    /**
     * @throws Throwable
     * @return void
     */
    public function testStoreImplementationPage(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageBody = $this->makePageData();
        $response = $this->postJson($this->getUrlPages($implementation), $pageBody, $this->makeApiHeaders($proxy));

        $this->assertImplementationPageSaved($response->json('data.id'), $pageBody);
        $response->assertSuccessful();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testStoreInvalidImplementationPage(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageData = $this->makePageData();

        $response = $this->postJson(
            $this->getUrlPages($implementation),
            array_fill_keys(array_keys($pageData), null),
            $this->makeApiHeaders($proxy),
        );

        $response->assertJsonValidationErrors(array_keys(Arr::except($pageData, [
            'state', 'description', 'description_position', 'description_alignment', 'blocks', 'external_url',
        ])));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateImplementationPage(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageBody = $this->makePageData();

        $response = $this->postJson(
            $this->getUrlPages($implementation),
            $pageBody,
            $this->makeApiHeaders($proxy)
        )->assertSuccessful();

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $urlPage = $this->getUrlPages($implementation, $implementationPage);

        $state = $implementationPage->state == ImplementationPage::STATE_DRAFT ?
            ImplementationPage::STATE_PUBLIC :
            ImplementationPage::STATE_DRAFT;

        $updateBody = [
            'state' => $state,
            'external' => $implementationPage->external,
            'external_url' => $implementationPage->external_url,
        ];

        $this->patchJson($urlPage, $updateBody, $this->makeApiHeaders($proxy))->assertSuccessful();
        $this->assertImplementationPageSaved($implementationPage->id, $updateBody);

        $pageBody = $this->makePageData(['page_type' => $implementationPage->page_type]);
        $response = $this->patchJson($urlPage, $pageBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();
        $response->assertJsonStructure(Arr::undot($this->pageResourceStructure));
        $this->assertImplementationPageSaved($implementationPage->id, $pageBody);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateInvalidImplementationPage(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $proxy = $this->makeIdentityProxy($implementation->organization->identity);
        $pageBody = $this->makePageData();
        $bodyEmpty = array_fill_keys(array_keys($pageBody), null);
        $response = $this->postJson($this->getUrlPages($implementation), $pageBody, $this->makeApiHeaders($proxy));

        $response->assertSuccessful();

        $implementationPage = ImplementationPage::find($response->json('data.id'));
        $implementationPageUrl = $this->getUrlPages($implementation, $implementationPage);

        $response = $this->patchJson($implementationPageUrl, $bodyEmpty, $this->makeApiHeaders($proxy));

        $response->assertJsonValidationErrors(array_keys(Arr::except($pageBody, [
            'state', 'description', 'description_position', 'description_alignment', 'blocks',
            'external_url', 'page_type',
        ])));
    }
}
