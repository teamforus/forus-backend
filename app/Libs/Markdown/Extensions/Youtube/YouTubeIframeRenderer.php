<?php


namespace App\Libs\Markdown\Extensions\Youtube;

use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;
use Zoon\CommonMark\Ext\YouTubeIframe\YouTubeIframe;

final class YouTubeIframeRenderer implements InlineRendererInterface {
    private $allowFullScreen;
    private $wrapperClass;

    /**
     * YouTubeIframeRenderer constructor.
     * @param $wrapperClass
     * @param bool $allowFullScreen
     */
    public function __construct($wrapperClass, $allowFullScreen) {
        $this->wrapperClass = $wrapperClass;
        $this->allowFullScreen = $allowFullScreen;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer) {
        if (!($inline instanceof YouTubeIframe)) {
            throw new \InvalidArgumentException('Incompatible inline type: ' . get_class($inline));
        }

        $src = "https://www.youtube.com/embed/{$inline->getUrl()->getVideoId()}";
        $startTimestamp = $inline->getUrl()->getStartTimestamp();

        if ($startTimestamp !== null) {
            $src .= "?start={$startTimestamp}";
        }

        return new HtmlElement('div', [
            'class' => $this->wrapperClass,
        ], new HtmlElement('iframe', [
            'src' => $src,
            'frameborder' => 0,
            'allowfullscreen' => $this->allowFullScreen,
        ]));
    }
}