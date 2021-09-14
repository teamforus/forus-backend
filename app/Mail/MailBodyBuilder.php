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
    protected $mailBody = [];

    /**
     * MailBodyBuilder constructor.
     * @param array $mailBody
     */
    public function __construct($mailBody = [])
    {
        $this->mailBody = $mailBody;
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h1(string $text, $styles = []): MailBodyBuilder
    {
        return $this->block('h1', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h2(string $text, $styles = []): MailBodyBuilder
    {
        return $this->block('h2', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h3(string $text, $styles = []): MailBodyBuilder
    {
        return $this->block('h3', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h4(string $text, $styles = []): MailBodyBuilder
    {
        return $this->block('h4', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function h5(string $text, $styles = []): MailBodyBuilder
    {
        return $this->block('h5', $text, $styles);
    }

    /**
     * @param string $text
     * @param array $styles
     * @return MailBodyBuilder
     */
    public function text(string $text, $styles = []): MailBodyBuilder
    {
        return $this->block('text', $text, $styles);
    }

    /**
     * @param string $html
     * @param array $globalStyles
     * @return MailBodyBuilder
     */
    public function markdownHtml(string $html, $globalStyles = 'text_left'): MailBodyBuilder
    {
        $html = $this->addStylesToMarkdownHtml($html, $globalStyles);

        return $this->block('markdown', $html);
    }

    /**
     * @param string $html
     * @param string $globalStyles
     * @return array|string|string[]
     */
    public function addStylesToMarkdownHtml(string $html, $globalStyles = 'text_left')
    {
        $styles = config('forus.mail_styles');

        $globalStyles = array_reduce(array_filter((array) $globalStyles), function($list, $key) use ($styles) {
            return $list . ' ' . ($styles[$key] ?? '');
        }, '');

        $replaces = [
            "<h1>"  => "<h1 style='" . $styles['h1'] . ' ' . $globalStyles . "'>",
            "<h2>"  => "<h2 style='" . $styles['h2'] . ' ' . $globalStyles . "'>",
            "<h3>"  => "<h3 style='" . $styles['h3'] . ' ' . $globalStyles . "'>",
            "<h4>"  => "<h4 style='" . $styles['h4'] . ' ' . $globalStyles . "'>",
            "<h5>"  => "<h5 style='" . $styles['h5'] . ' ' . $globalStyles . "'>",
            "<p>"  => "<p style='" . $styles['text'] . ' ' . $globalStyles . "'>",
            "<a href"  => "<a style='" . $styles['link'] . ' ' . $globalStyles . "' href",
        ];

        return str_replace(array_keys($replaces), array_values($replaces), $html);
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
    public function link(
        string $url = '',
        string $text = '',
        $styles = []
    ): MailBodyBuilder {
        return $this->button('link', $url, $text ?: $url, $styles);
    }

    /**
     * @param string $text
     * @param string $url
     * @param array $styles
     * @return $this
     */
    public function button_primary(
        string $url = '',
        string $text = '',
        $styles = []
    ): MailBodyBuilder {
        return $this->button('button_primary', $url, $text, $styles);
    }

    /**
     * @param string $text
     * @param string $url
     * @param array $styles
     * @return $this
     */
    public function button_success(
        string $url = '',
        string $text = '',
        $styles = []
    ): MailBodyBuilder {
        return $this->button('button_success', $url, $text, $styles);
    }

    /**
     * @param string $text
     * @param string $url
     * @param array $styles
     * @return $this
     */
    public function button_danger(
        string $url = '',
        string $text = '',
        $styles = []
    ): MailBodyBuilder {
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
        $styles = []
    ): MailBodyBuilder {
        $this->mailBody[] = [[$type] + (array) $styles, $text, $url];
        return $this;
    }

    /**
     * @param string $type
     * @param string $text
     * @param array $styles
     * @return $this
     */
    public function block(
        string $type = 'h1',
        string $text = '',
        $styles = []
    ): MailBodyBuilder {
        $this->mailBody[] = [array_merge((array) $type, (array) $styles), $text];
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