<?php

namespace App\Traits;

use App\Services\MediaService\MediaPreset;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use DOMDocument;
use DOMElement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Trait HasMarkdownDescription
 * @property string $description
 * @property string $description_html
 * @property string $description_text
 * @package App\Traits
 * @mixin  \Eloquent
 * @mixin  HasMedia
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

    /**
     * @return array
     */
    public function getDescriptionMarkdownMediaPaths(): array
    {
        if (!$this->descriptionToHtml()) {
            return [];
        }

        $htmlDom = new DOMDocument();

        $htmlDom->loadHTML($this->descriptionToHtml());
        $images = $htmlDom->getElementsByTagName('img');
        $linksArray = [];

        /** @var DOMElement $image */
        foreach ($images as $image) {
            $imageSrc = $image->getAttribute('src');

            if (Str::contains($imageSrc, '/media/')) {
                $srcSegments = array();
                preg_match('/\/media\/.*/', $imageSrc, $srcSegments);

                $linksArray[] = $srcSegments[0];
            }
        }

        return $linksArray;
    }

    /**
     * @return Builder|Media
     */
    public function getDescriptionMarkdownMediaQuery(): Builder|Media
    {
        return Media::whereRelation('presets', function(Builder|MediaPreset $builder) {
            $builder->whereIn('path', $this->getDescriptionMarkdownMediaPaths());
        });
    }

    /**
     * @param string $mediaType
     * @return bool
     */
    public function syncDescriptionMarkdownMedia(string $mediaType): bool
    {
        if (!$this->descriptionToHtml()) {
            return false;
        }

        $uid = $this->getDescriptionMarkdownMediaQuery()
            ->where('type', $mediaType)
            ->where(function (Builder $builder) {
                $builder->whereNull('mediable_id');
                $builder->orWhereHasMorph('mediable', $this->getMorphClass(), function(Builder $builder) {
                    $builder->where('mediable_id', $this->id);
                });
            })->pluck('uid')->toArray();

        return $this->syncMedia($uid, $mediaType);
    }
}