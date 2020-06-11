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
    protected function block(
        string $type = 'h1',
        string $text = '',
        $styles = []
    ): MailBodyBuilder {
        $this->mailBody[] = [[$type] + (array) $styles, $text];
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
}