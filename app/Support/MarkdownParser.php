<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\MarkdownConverter;

class MarkdownParser
{
    protected MarkdownConverter $converter;

    public function __construct()
    {
        $this->converter = $this->makeConverter();
    }

    /**
     * Convert Markdown to HTML.
     *
     * @param string|null $markdown
     * @throws CommonMarkException
     * @return string
     */
    public function toHtml(?string $markdown = ''): string
    {
        return $this->converter->convert($markdown ?: '')->getContent();
    }

    /**
     * Convert Markdown to plain text by stripping HTML and whitespace normalization.
     *
     * @param string|null $markdown
     * @throws CommonMarkException
     * @return string
     */
    public function toText(?string $markdown = ''): string
    {
        return trim(preg_replace('/\s+/', ' ', e(strip_tags($this->toHtml($markdown)))));
    }

    /**
     * Create a new Markdown converter instance.
     *
     * @return MarkdownConverter
     */
    protected function makeConverter(): MarkdownConverter
    {
        $config = Config::get('markdown');
        $environment = new Environment(Arr::except($config, ['extensions', 'views']));

        foreach ((array) Arr::get($config, 'extensions') as $extension) {
            $environment->addExtension(resolve($extension));
        }

        return new MarkdownConverter($environment);
    }
}
