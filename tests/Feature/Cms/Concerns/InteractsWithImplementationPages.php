<?php

namespace Tests\Feature\Cms\Concerns;

use App\Helpers\Arr;
use App\Models\Faq;
use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;
use App\Services\MediaService\Models\Media;
use Exception;
use Throwable;

trait InteractsWithImplementationPages
{
    protected array $pageResourceStructure = [
        'data' => [
            'id', 'page_type', 'external', 'external_url', 'state', 'blocks', 'cms_blocks', 'description',
            'description_alignment', 'description_position', 'description_html',
            'implementation_id', 'url_webshop',
        ],
        'data.cms_blocks.*' => [
            'id', 'block_type_key', 'state', 'order', 'values', 'values_html', 'media', 'items',
        ],
        'data.cms_blocks.*.items.*' => [
            'id', 'item_type_key', 'order', 'values', 'values_html', 'media',
        ],
        'data.implementation' => [
            'id', 'name', 'url_webshop', 'organization_id',
        ],
        'data.blocks.*' => [
            'id', 'label', 'title', 'description', 'description_html',
            'button_text', 'button_link', 'button_target_blank', 'button_enabled',
        ],
        'data.faq.*' => [
            'id', 'title', 'description', 'description_html',
        ],
        'data.blocks.*.media' => [
            'identity_address', 'original_name', 'dominant_color', 'is_dark', 'type', 'ext', 'uid',
        ],
        'data.blocks.*.media.sizes' => [],
    ];

    /**
     * @param Media $media
     * @return string
     */
    protected function makeMarkdownDescription(Media $media): string
    {
        return implode("  \n", [
            '# ' . $this->faker->text(50),
            '![](' . $media->urlPublic('public') . ')',
            '# ' . $this->faker->text(50),
        ]);
    }

    /**
     * @throws Exception
     * @return array
     */
    protected function makePageBlockData(): array
    {
        return [
            'button_enabled' => (bool) rand(0, 1),
            'button_link' => $this->faker->url(),
            'button_link_label' => $this->faker->text(),
            'button_target_blank' => (bool) rand(0, 1),
            'button_text' => $this->faker->text(100),
            'description' => $this->faker->text(500),
            'label' => $this->faker->text(30),
            'title' => $this->faker->text(100),
            'media_uid' => $this->makeMedia('implementation_block_media')->uid,
        ];
    }

    /**
     * @throws Exception
     * @return array
     */
    protected function makeFAQData(): array
    {
        return [
            'title' => $this->faker->text(100),
            'type' => Faq::TYPE_QUESTION,
            'description' => $this->makeMarkdownDescription($this->makeMedia('cms_media')),
        ];
    }

    /**
     * @param array $replace
     * @throws Exception
     * @return array
     */
    protected function makePageData(array $replace = []): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);
        $pageTypes = Arr::pluck(ImplementationPage::PAGE_TYPES, 'key');
        $pageTypes = array_diff($pageTypes, $implementation->pages()->pluck('page_type')->toArray());
        $external = (bool) rand(0, 1);

        return array_merge([
            'state' => ImplementationPage::STATE_DRAFT,
            'blocks' => array_map(fn () => $this->makePageBlockData(), range(0, rand(1, 5))),
            'external' => $external,
            'page_type' => Arr::random($pageTypes),
            'description' => $this->makeMarkdownDescription($this->makeMedia('cms_media')),
            'external_url' => $external ? $this->faker->url() : null,
            'description_position' => Arr::random(ImplementationPage::DESCRIPTION_POSITIONS),
            'description_alignment' => Arr::random(['left', 'center', 'right']),
        ], $replace);
    }

    /**
     * @return array
     */
    protected function blockKeys(): array
    {
        return [
            'button_enabled',
            'button_link',
            'button_link_label',
            'button_target_blank',
            'button_text',
            'description',
            'label',
            'title',
        ];
    }

    /**
     * @param int $id
     * @param array $body
     * @throws Throwable
     * @return void
     */
    protected function assertImplementationPageSaved(int $id, array $body): void
    {
        $page = ImplementationPage::find($id);

        $body['external'] = !$page::isInternalType($page->page_type) && $body['external'];
        $body['external_url'] = $body['external'] ? $body['external_url'] : null;

        foreach (Arr::except($body, ['faq', 'blocks', 'cms_blocks']) as $key => $value) {
            $this->assertEquals($value, $page[$key]);
        }

        if (isset($body['blocks'])) {
            $blockKeys = $this->blockKeys();

            $this->assertCount($page->blocks->count(), $body['blocks']);

            /** @var ImplementationBlock $block */
            foreach ($page->blocks as $index => $block) {
                $this->assertEquals($block->only($blockKeys), Arr::only($body['blocks'][$index], $blockKeys));

                if (isset($body['blocks'][$index]['media_uid'])) {
                    $this->assertEquals($block->photo->uid, $body['blocks'][$index]['media_uid']);
                }
            }
        }

        if (isset($body['faq'])) {
            $faqKeys = ['title', 'type', 'description'];

            $this->assertEquals(count($body['faq']), $page->faq->count());

            /** @var Faq $faq */
            foreach ($page->faq as $index => $faq) {
                $this->assertEquals($faq->only($faqKeys), Arr::only($body['faq'][$index], $faqKeys));
                $this->assertEquals(1, $faq->medias()->count());
            }
        }
    }
}
