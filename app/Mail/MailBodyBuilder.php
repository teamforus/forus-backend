<?php


namespace App\Mail;

/**
 * Class MailBodyBuilder
 * @package App\Mail
 */
class MailBodyBuilder
{
    /**
     * @var array
     */
    protected array $mailBody = [];

    /**
     * MailBodyBuilder constructor.
     * @param array $mailBody
     */
    public function __construct(array $mailBody = [])
    {
        $this->mailBody = $mailBody;
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h1(string $text, array $styles = []): MailBodyBuilder
    {
        return $this->block('h1', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h2(string $text, array $styles = []): MailBodyBuilder
    {
        return $this->block('h2', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h3(string $text, array $styles = []): MailBodyBuilder
    {
        return $this->block('h3', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h4(string $text, array $styles = []): MailBodyBuilder
    {
        return $this->block('h4', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h5(string $text, array $styles = []): MailBodyBuilder
    {
        return $this->block('h5', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function text(string $text, array $styles = []): MailBodyBuilder
    {
        return $this->block('text', $text, $styles);
    }

    /**
     * @param string $html
     * @param string $globalStyles
     * @param string|null $textColor
     * @return MailBodyBuilder
     */
    public function markdownHtml(
        string $html,
        string $globalStyles = 'text_left',
        ?string $textColor = null
    ): MailBodyBuilder {
        $html = $this->addStylesToMarkdownHtml($html, $globalStyles, $textColor);

        return $this->block('markdown', $html);
    }

    /**
     * @param string $markdown
     * @param array $data
     * @param string $globalStyles
     * @param string|null $textColor
     * @return MailBodyBuilder
     */
    public function markdown(
        string $markdown,
        array $data = [],
        string $globalStyles = 'text_left',
        ?string $textColor = null
    ): MailBodyBuilder {
        $templateHtml = resolve('markdown.converter')->convert($markdown)->getContent();
        $templateHtml = str_var_replace($templateHtml, $data);

        return $this->markdownHtml($templateHtml, $globalStyles, $textColor);
    }

    /**
     * @param string $html
     * @param string $globalStyles
     * @param string|null $textColor
     * @return false|string
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function addStylesToMarkdownHtml(
        string $html,
        string $globalStyles = 'text_left',
        ?string $textColor = null
    ): bool|string {
        $html = str_replace('&amp;nbsp;', ' ', $html);
        $styles = config('forus.mail_styles');
        $textColor = $textColor ? "; color: $textColor;" : '';
        $globalStyles = array_reduce(array_filter((array) $globalStyles), function($list, $key) use ($styles) {
            return $list . ' ' . ($styles[$key] ?? '');
        }, '');

        $documentOptions = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR;
        $document = new \DomDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, $documentOptions);

        $styles['p'] = $styles['text'] ?? '';
        $styles['a'] = $styles['link'] ?? '';

        foreach (array_only($styles, ['h1', 'h2', 'h3', 'h4', 'h5', 'p', 'a']) as $tagName => $tagStyles) {
            $elements = $document->getElementsByTagName($tagName);

            for ($i = $elements->length; --$i >= 0; ) {
                $attrStyles = $elements->item($i)->getAttribute('style');
                $stylesValue = $tagStyles . ' ' . $globalStyles;
                $stylesValue = ($tagName == 'a' ? $stylesValue . $textColor : $stylesValue);
                $elements->item($i)->setAttribute('style', $stylesValue . ' ' . $attrStyles);
            }
        }

        return $document->saveHTML();
    }

    /**
     * @return MailBodyBuilder
     */
    public function space(): MailBodyBuilder
    {
        return $this->block('space');
    }

    /**
     * @return MailBodyBuilder
     */
    public function separator(): MailBodyBuilder
    {
        return $this->block('separator');
    }

    /**
     * @param string $text
     * @param string $url
     * @param array $styles
     * @return $this
     */
    public function link(string $url, string $text = '', array $styles = []): MailBodyBuilder
    {
        return $this->button('link', $url, $text ?: $url, $styles);
    }

    /**
     * @param string $text
     * @param string $url
     * @param array $styles
     * @return $this
     */
    public function button_primary(string $url, string $text = '', array $styles = []): MailBodyBuilder
    {
        return $this->button('button_primary', $url, $text, $styles);
    }

    /**
     * @param string $text
     * @param string $url
     * @param array $styles
     * @return $this
     * @noinspection PhpUnused
     */
    public function button_success(string $url, string $text = '', array $styles = []): MailBodyBuilder
    {
        return $this->button('button_success', $url, $text, $styles);
    }

    /**
     * @param string $text
     * @param string $url
     * @param array $styles
     * @return $this
     * @noinspection PhpUnused
     */
    public function button_danger(string $url, string $text = '', array $styles = []): MailBodyBuilder
    {
        return $this->button('button_danger', $url, $text, $styles);
    }

    /**
     * @param string $type
     * @param string $url
     * @param string $text
     * @param array $styles
     * @return $this
     */
    protected function button(
        string $type = 'button_primary',
        string $url = '',
        string $text = '',
        array $styles = []
    ): MailBodyBuilder {
        $this->mailBody[] = [array_merge((array) $type, $styles), $text, $url];
        return $this;
    }

    /**
     * @param string $type
     * @param string $text
     * @param array $styles
     * @return $this
     */
    public function block(string $type = 'h1', string $text = '', array $styles = []): MailBodyBuilder
    {
        $this->mailBody[] = [array_merge((array) $type, $styles), $text];
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->mailBody;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->mailBody);
    }

    /**
     * @param MailBodyBuilder ...$builders
     * @return MailBodyBuilder
     */
    public function merge(...$builders): MailBodyBuilder
    {
        $body = $this->mailBody;

        foreach ($builders as $builder) {
            foreach ($builder->mailBody as $mailBodyItem) {
                $body[] = $mailBodyItem;
            }
        }

        return new MailBodyBuilder($body);
    }

    /**
     * @return $this
     */
    public function pop(): MailBodyBuilder
    {
        array_pop($this->mailBody);
        return $this;
    }

    /**
     * @return MailBodyBuilder
     */
    public static function create(): MailBodyBuilder
    {
        return new self();
    }
}