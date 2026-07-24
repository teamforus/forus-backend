<?php

namespace Tests\Unit\Cms\Configs;

use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use Tests\Unit\Cms\CmsBlockTestCase;

class CmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testSharedSectionFieldsMatchExpectedSchema(): void
    {
        $config = new class () extends CmsBlockConfig {
            /**
             * @return string
             */
            public function key(): string
            {
                return 'test';
            }

            /**
             * @return string
             */
            public function name(): string
            {
                return 'Test';
            }

            /**
             * @return string[]
             */
            public function allowedPageTypes(): array
            {
                return [];
            }

            /**
             * @return array[]
             */
            public function fields(): array
            {
                return [
                    $this->sectionTitleField(),
                    $this->sectionDescriptionField(),
                    $this->sectionBackgroundColorField(),
                    $this->sectionSpacingField(),
                ];
            }

            /**
             * @return array[]
             */
            public function itemTypes(): array
            {
                return [];
            }

            /**
             * @param string $itemTypeKey
             * @return array[]
             */
            public function itemFields(string $itemTypeKey): array
            {
                return [];
            }
        };

        $this->assertSectionFields($config);
    }
}
