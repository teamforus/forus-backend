<?php

namespace Tests\Unit\Cms\Configs;

use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\Configs\ProviderSignUpCmsBlockConfig;
use Illuminate\Validation\ValidationException;
use Tests\Unit\Cms\CmsBlockTestCase;

class ProviderSignUpCmsBlockConfigTest extends CmsBlockTestCase
{
    /**
     * @return void
     */
    public function testProviderSignUpConfigFieldsMatchExpectedSchema(): void
    {
        $config = new ProviderSignUpCmsBlockConfig();

        $this->assertSame([
            'section_title',
            'section_description',
            'section_background_color',
            'section_spacing',
            'image',
            'button_text',
            'login_enabled',
            'login_text',
            'login_link_text',
        ], array_column($config->fields(), 'key'));
        $this->assertSame([], $config->itemTypes());

        $sectionTitle = $config->field('section_title');
        $sectionDescription = $config->field('section_description');
        $image = $config->field('image');
        $buttonText = $config->field('button_text');
        $loginEnabled = $config->field('login_enabled');
        $loginText = $config->field('login_text');
        $loginLinkText = $config->field('login_link_text');

        $this->assertSame('Aanmelden als aanbieder', $sectionTitle['default']);
        $this->assertStringContainsString('Door het online formulier', $sectionDescription['default']);

        $this->assertSame(CmsBlockConfig::TYPE_MEDIA, $image['type']);
        $this->assertSame('implementation_block_media', $image['media_type']);
        $this->assertFalse($image['required']);
        $this->assertFalse($image['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $buttonText['type']);
        $this->assertTrue($buttonText['required']);
        $this->assertSame('Aanmelden', $buttonText['default']);
        $this->assertSame(100, $buttonText['max']);
        $this->assertTrue($buttonText['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_BOOLEAN, $loginEnabled['type']);
        $this->assertFalse($loginEnabled['required']);
        $this->assertTrue($loginEnabled['default']);
        $this->assertFalse($loginEnabled['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $loginText['type']);
        $this->assertSame(['login_enabled', true], $loginText['visible_if']);
        $this->assertFalse($loginText['required']);
        $this->assertSame('Heeft u al een account?', $loginText['default']);
        $this->assertSame(200, $loginText['max']);
        $this->assertTrue($loginText['translatable']);

        $this->assertSame(CmsBlockConfig::TYPE_TEXT, $loginLinkText['type']);
        $this->assertSame(['login_enabled', true], $loginLinkText['visible_if']);
        $this->assertFalse($loginLinkText['required']);
        $this->assertSame('Log dan in', $loginLinkText['default']);
        $this->assertSame(100, $loginLinkText['max']);
        $this->assertTrue($loginLinkText['translatable']);
    }

    /**
     * @throws ValidationException
     * @return void
     */
    public function testAcceptsValidProviderSignUpBlockWithoutItems(): void
    {
        $page = $this->makeCmsPageAsOwner(ImplementationPage::TYPE_PROVIDER);
        $blocks = $this->makeValidCmsProviderSignUpBlocksPayload();

        $this->assertBlocksValid($page, null, $blocks);
    }
}
