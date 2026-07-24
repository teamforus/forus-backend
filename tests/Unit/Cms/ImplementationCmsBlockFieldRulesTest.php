<?php

namespace Tests\Unit\Cms;

use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use App\Services\CmsService\ImplementationBlocks\Validation\ImplementationCmsBlockRuleSet;
use Illuminate\Support\Facades\Validator;

class ImplementationCmsBlockFieldRulesTest extends CmsBlockTestCase
{
    /**
     * @var CmsBlockConfig[]
     */
    private array $originalBlockConfigs;

    private CmsBlockConfig $fieldConfig;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalBlockConfigs = ImplementationCmsBlockService::getBlockConfigs();
        $this->fieldConfig = new class () extends CmsBlockConfig {
            public const string KEY = 'field_rules_test';

            /**
             * @return string
             */
            public function key(): string
            {
                return self::KEY;
            }

            /**
             * @return string
             */
            public function name(): string
            {
                return 'Field rules test';
            }

            /**
             * @return string[]
             */
            public function allowedPageTypes(): array
            {
                return [ImplementationPage::TYPE_HOME];
            }

            /**
             * @return array[]
             */
            public function fields(): array
            {
                return [
                    $this->makeField('required_text', self::TYPE_TEXT, [
                        'required' => true,
                        'max' => 5,
                    ]),
                    $this->makeField('required_if_text', self::TYPE_TEXT, [
                        'required_if' => ['enabled', true],
                        'max' => 5,
                    ]),
                    $this->makeField('required_with_text', self::TYPE_TEXT, [
                        'required_with' => 'trigger',
                        'max' => 5,
                    ]),
                    $this->makeField('enabled', self::TYPE_BOOLEAN),
                    $this->makeField('mode', self::TYPE_TEXT, [
                        'options' => [
                            ['value' => 'shown', 'name' => 'Shown'],
                            ['value' => 'hidden', 'name' => 'Hidden'],
                        ],
                    ]),
                    $this->makeField('trigger', self::TYPE_TEXT, [
                        'max' => 10,
                    ]),
                    $this->makeField('boolean_visible_text', self::TYPE_TEXT, [
                        'visible_if' => ['enabled', true],
                        'max' => 5,
                    ]),
                    $this->makeField('scalar_visible_text', self::TYPE_TEXT, [
                        'visible_if' => ['mode', 'shown'],
                        'max' => 5,
                    ]),
                    $this->makeField('filled_visible_text', self::TYPE_TEXT, [
                        'visible_if_filled' => 'trigger',
                        'max' => 5,
                    ]),
                    $this->makeField('text', self::TYPE_TEXT, [
                        'max' => 5,
                    ]),
                    $this->makeField('text_option', self::TYPE_TEXT, [
                        'options' => [
                            ['value' => 'alpha', 'name' => 'Alpha'],
                            ['value' => 'beta', 'name' => 'Beta'],
                        ],
                    ]),
                    $this->makeField('url', self::TYPE_URL, [
                        'max' => 30,
                    ]),
                    $this->makeField('markdown', self::TYPE_MARKDOWN, [
                        'max' => 5,
                    ]),
                    $this->makeField('number', self::TYPE_NUMBER, [
                        'min' => 1,
                        'max' => 3,
                    ]),
                    $this->makeField('number_option', self::TYPE_NUMBER, [
                        'options' => [
                            ['value' => 1, 'name' => 'One'],
                            ['value' => 3, 'name' => 'Three'],
                        ],
                    ]),
                    $this->makeField('boolean', self::TYPE_BOOLEAN),
                    $this->makeField('color', self::TYPE_COLOR),
                    $this->makeField('unsupported', 'unsupported'),
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

            /**
             * @param string $key
             * @param string $type
             * @param array $overrides
             * @return array
             */
            private function makeField(string $key, string $type, array $overrides = []): array
            {
                return [
                    'key' => $key,
                    'name' => $key,
                    'type' => $type,
                    'required' => false,
                    'translatable' => false,
                    ...$overrides,
                ];
            }
        };

        ImplementationCmsBlockService::addBlockConfig($this->fieldConfig);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        ImplementationCmsBlockService::setBlockConfigs($this->originalBlockConfigs, false);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testAppliesRequiredAndNullableRules(): void
    {
        $page = $this->makeCmsPageAsOwner();

        $this->assertValuesValid($page, $this->validValues());
        $this->assertValuesValid($page, $this->validValues(['text' => null]));

        foreach ([[], ['required_text' => null]] as $values) {
            $this->assertValuesInvalid($page, $values, ['cms_blocks.0.values.required_text']);
        }
    }

    /**
     * @return void
     */
    public function testAppliesConditionalPresenceRules(): void
    {
        $page = $this->makeCmsPageAsOwner();

        $this->assertValuesInvalid(
            $page,
            $this->validValues(['enabled' => true]),
            ['cms_blocks.0.values.required_if_text'],
        );
        $this->assertValuesValid($page, $this->validValues(['enabled' => false]));

        $this->assertValuesInvalid(
            $page,
            $this->validValues(['trigger' => 'set']),
            ['cms_blocks.0.values.required_with_text'],
        );
        $this->assertValuesValid($page, $this->validValues(['trigger' => '']));
    }

    /**
     * @return void
     */
    public function testExcludesFieldsThatAreNotVisible(): void
    {
        $page = $this->makeCmsPageAsOwner();

        foreach ([false, 0, '0'] as $disabled) {
            $values = $this->validatedValues($page, $this->validValues([
                'enabled' => $disabled,
                'boolean_visible_text' => 123,
            ]));

            $this->assertArrayNotHasKey('boolean_visible_text', $values);
        }

        foreach ([true, 1, '1'] as $enabled) {
            $this->assertValuesInvalid($page, $this->validValues([
                'enabled' => $enabled,
                'required_if_text' => 'value',
                'boolean_visible_text' => 123,
            ]), ['cms_blocks.0.values.boolean_visible_text']);
        }

        $values = $this->validatedValues($page, $this->validValues([
            'mode' => 'hidden',
            'scalar_visible_text' => 123,
        ]));
        $this->assertArrayNotHasKey('scalar_visible_text', $values);

        $this->assertValuesInvalid($page, $this->validValues([
            'mode' => 'shown',
            'scalar_visible_text' => 123,
        ]), ['cms_blocks.0.values.scalar_visible_text']);

        foreach ([null, ''] as $trigger) {
            $values = $this->validatedValues($page, $this->validValues([
                'trigger' => $trigger,
                'filled_visible_text' => 123,
            ]));

            $this->assertArrayNotHasKey('filled_visible_text', $values);
        }

        $values = $this->validatedValues($page, $this->validValues([
            'filled_visible_text' => 123,
        ]));
        $this->assertArrayNotHasKey('filled_visible_text', $values);

        $this->assertValuesInvalid($page, $this->validValues([
            'trigger' => 'set',
            'required_with_text' => 'value',
            'filled_visible_text' => 123,
        ]), ['cms_blocks.0.values.filled_visible_text']);
    }

    /**
     * @return void
     */
    public function testValidatesTextFields(): void
    {
        $page = $this->makeCmsPageAsOwner();

        $this->assertValuesValid($page, $this->validValues(['text' => 'abcde']));

        foreach ([123, 'abcdef'] as $invalidText) {
            $this->assertValuesInvalid(
                $page,
                $this->validValues(['text' => $invalidText]),
                ['cms_blocks.0.values.text'],
            );
        }

        foreach (['alpha', 'beta'] as $textOption) {
            $this->assertValuesValid($page, $this->validValues(['text_option' => $textOption]));
        }

        $this->assertValuesInvalid(
            $page,
            $this->validValues(['text_option' => 'gamma']),
            ['cms_blocks.0.values.text_option'],
        );
    }

    /**
     * @return void
     */
    public function testValidatesUrlFields(): void
    {
        $page = $this->makeCmsPageAsOwner();

        foreach (['http://example.com', 'https://example.com'] as $url) {
            $this->assertValuesValid($page, $this->validValues(['url' => $url]));
        }

        foreach (['/relative', 'javascript:alert(1)'] as $url) {
            $this->assertValuesInvalid(
                $page,
                $this->validValues(['url' => $url]),
                ['cms_blocks.0.values.url'],
            );
        }

        $this->assertValuesInvalid(
            $page,
            $this->validValues(['url' => 'https://example.com/' . str_repeat('a', 20)]),
            ['cms_blocks.0.values.url'],
        );
    }

    /**
     * @return void
     */
    public function testValidatesMarkdownFields(): void
    {
        $page = $this->makeCmsPageAsOwner();

        $this->assertValuesValid($page, $this->validValues(['markdown' => 'abcde']));

        foreach ([123, 'abcdef'] as $markdown) {
            $this->assertValuesInvalid(
                $page,
                $this->validValues(['markdown' => $markdown]),
                ['cms_blocks.0.values.markdown'],
            );
        }
    }

    /**
     * @return void
     */
    public function testValidatesNumberFields(): void
    {
        $page = $this->makeCmsPageAsOwner();

        foreach ([1, 3] as $number) {
            $this->assertValuesValid($page, $this->validValues(['number' => $number]));
        }

        foreach ([0, 4, 1.5] as $number) {
            $this->assertValuesInvalid(
                $page,
                $this->validValues(['number' => $number]),
                ['cms_blocks.0.values.number'],
            );
        }

        foreach ([1, 3] as $numberOption) {
            $this->assertValuesValid($page, $this->validValues(['number_option' => $numberOption]));
        }

        $this->assertValuesInvalid(
            $page,
            $this->validValues(['number_option' => 2]),
            ['cms_blocks.0.values.number_option'],
        );
    }

    /**
     * @return void
     */
    public function testValidatesBooleanFields(): void
    {
        $page = $this->makeCmsPageAsOwner();

        foreach ([true, false, 1, 0, '1', '0'] as $boolean) {
            $this->assertValuesValid($page, $this->validValues(['boolean' => $boolean]));
        }

        foreach (['true', 'false', 2] as $boolean) {
            $this->assertValuesInvalid(
                $page,
                $this->validValues(['boolean' => $boolean]),
                ['cms_blocks.0.values.boolean'],
            );
        }
    }

    /**
     * @return void
     */
    public function testValidatesColorFields(): void
    {
        $page = $this->makeCmsPageAsOwner();

        foreach (['#315EFD', '#315EFD80'] as $color) {
            $this->assertValuesValid($page, $this->validValues(['color' => $color]));
        }

        foreach (['red', '#GGGGGG'] as $color) {
            $this->assertValuesInvalid(
                $page,
                $this->validValues(['color' => $color]),
                ['cms_blocks.0.values.color'],
            );
        }
    }

    /**
     * @return void
     */
    public function testProhibitsUnsupportedFieldTypes(): void
    {
        $page = $this->makeCmsPageAsOwner();

        $this->assertValuesValid($page, $this->validValues());
        $this->assertValuesInvalid(
            $page,
            $this->validValues(['unsupported' => 'value']),
            ['cms_blocks.0.values.unsupported'],
        );
    }

    /**
     * @param array $replace
     * @return array
     */
    private function validValues(array $replace = []): array
    {
        return [
            'required_text' => 'value',
            ...$replace,
        ];
    }

    /**
     * @param array $values
     * @return array
     */
    private function makeBlocks(array $values): array
    {
        return [[
            'block_type_key' => $this->fieldConfig->key(),
            'values' => $values,
            'items' => [],
        ]];
    }

    /**
     * @param ImplementationPage $page
     * @param array $values
     * @return void
     */
    private function assertValuesValid(ImplementationPage $page, array $values): void
    {
        $this->assertBlocksValid($page, null, $this->makeBlocks($values));
    }

    /**
     * @param ImplementationPage $page
     * @param array $values
     * @param string[] $keys
     * @return void
     */
    private function assertValuesInvalid(ImplementationPage $page, array $values, array $keys): void
    {
        $blocks = $this->makeBlocks($values);

        $this->assertValidationErrors(function () use ($page, $blocks) {
            $this->validateBlocks($page, null, $blocks);
        }, $keys);
    }

    /**
     * @param ImplementationPage $page
     * @param array $values
     * @return array
     */
    private function validatedValues(ImplementationPage $page, array $values): array
    {
        $blocks = $this->makeBlocks($values);
        $validator = Validator::make(
            ['cms_blocks' => $blocks],
            ImplementationCmsBlockRuleSet::rules($page, null, $blocks),
        );

        $this->assertSame([], $validator->errors()->toArray());

        return $validator->validated()['cms_blocks'][0]['values'];
    }
}
