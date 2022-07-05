<?php

namespace App\Traits;

/**
 * Trait HasMarkdownDescription
 * @property string $description
 * @property string $description_html
 * @property string $description_text
 * @package App\Traits
 * @mixin  \Eloquent
 */
trait HasMarkdownDescription {
    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return $this->descriptionToHtml();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function descriptionToHtml(): string
    {
        return resolve('markdown.converter')->convert($this->description ?: '')->getContent();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function descriptionToText(): string
    {
        return trim(preg_replace('/\s+/', ' ', e(strip_tags($this->descriptionToHtml()))));
    }
}