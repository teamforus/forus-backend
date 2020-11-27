<?php

namespace App\Libs\Markdown\Extensions\Youtube;

use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use Zoon\CommonMark\Ext\YouTubeIframe\YouTubeIframe;
use Zoon\CommonMark\Ext\YouTubeIframe\YouTubeIframeProcessor;
use Zoon\CommonMark\Ext\YouTubeIframe\YouTubeLongUrlParser;
use Zoon\CommonMark\Ext\YouTubeIframe\YouTubeShortUrlParser;

class YouTubeIframeExtension implements ExtensionInterface
{
    /**
     * @param ConfigurableEnvironmentInterface $environment
     */
    public function register(ConfigurableEnvironmentInterface $environment): void {
        $environment->addEventListener(DocumentParsedEvent::class, new YouTubeIframeProcessor([
            new YouTubeLongUrlParser(),
            new YouTubeShortUrlParser(),
        ]))->addInlineRenderer(YouTubeIframe::class, new YouTubeIframeRenderer(
            (string) $environment->getConfig('youtube_iframe_wrapper_class', ''),
            (bool) $environment->getConfig('youtube_iframe_allowfullscreen', true)
        ));
    }
}