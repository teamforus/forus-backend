<?php

namespace App\Traits;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use App\Services\MediaService\Traits\HasMedia;
use DOMDocument;
use DOMElement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;
use Eloquent;

/**
 * @property string $description
 * @property string $description_html
 * @property string $description_text
 * @mixin Eloquent
 * @mixin HasMedia
 */
trait HasMarkdownDescription
{
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
        return $this->getMarkdownConverter()->convert($this->description ?: '')->getContent();
    }

    /**
     * @return MarkdownConverter
     */
    protected function getMarkdownConverter(): MarkdownConverter
    {
        $config = $this->getMarkdownConverterConfigs();
        $environment = new Environment(Arr::except($config, ['extensions', 'views']));

        foreach ((array) Arr::get($config, 'extensions') as $extension) {
            $environment->addExtension(resolve($extension));
        }

        return new MarkdownConverter($environment);
    }

    /**
     * @return array
     */
    protected function getMarkdownConverterConfigs(): array
    {
        return Config::get('markdown');
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
    protected function getDescriptionMarkdownMediaPaths(): array
    {
        if (!$this->descriptionToHtml()) {
            return [];
        }

        $htmlDom = new DOMDocument();

        $htmlDom->loadHTML($this->descriptionToHtml(), LIBXML_NOERROR);
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
     * @return Builder|MediaPreset
     */
    protected function getDescriptionMarkdownMediaPresetsQuery(): Builder|MediaPreset
    {
        return MediaPreset::query()->whereIn('path', $this->getDescriptionMarkdownMediaPaths());
    }

    /**
     * @return Builder|Media
     */
    public function getDescriptionMarkdownMediaQuery(): Builder|Media
    {
        return Media::whereRelation('presets', function(Builder|MediaPreset $builder) {
            $builder->whereIn('id', $this->getDescriptionMarkdownMediaPresetsQuery()->select('id'));
        });
    }

    /**
     * @param string $mediaType
     * @return Builder|Media
     */
    public function getDescriptionMarkdownMediaPresetsValidQuery(
        string $mediaType
    ): Builder|MediaPreset {
        $presets = $this->getDescriptionMarkdownMediaPresetsQuery();

        return $presets->whereHas('media', function (Builder|Media $builder) use ($mediaType) {
            $builder->where('type', $mediaType);
            $builder->where(function (Builder $builder) {
                $builder->whereNull('mediable_id');
                $builder->orWhereHasMorph('mediable', $this->getMorphClass(), function(Builder $builder) {
                    $builder->where('mediable_id', $this->getKey());
                });
            });
        });
    }

    /**
     * @param string $mediaType
     * @return Builder|Media
     */
    public function getDescriptionMarkdownMediaPresetsInValidQuery(
        string $mediaType,
    ): Builder|MediaPreset {
        $presets = $this->getDescriptionMarkdownMediaPresetsQuery();
        $validPresets = $this->getDescriptionMarkdownMediaPresetsValidQuery($mediaType);

        return (clone $presets)->whereNotIn('id', (clone $validPresets->select('id')));
    }

    /**
     * @param string $mediaType
     * @return bool
     * @throws \Throwable
     */
    public function syncDescriptionMarkdownMedia(string $mediaType): bool
    {
        $presetsInvalid = $this->getDescriptionMarkdownMediaPresetsInValidQuery($mediaType)->get();

        $description = $presetsInvalid->reduce(function ($description, MediaPreset $preset) use ($mediaType) {
            $newMedia = resolve('media')->cloneMedia($preset->media, $mediaType);
            $newMedia->update(['identity_address' => auth()->id()]);
            $newPreset = $newMedia->findPreset($preset->key);
            $imageUrl = $newPreset ?: $newMedia->presets()->where('key', '!=', 'original')->first();

            return str_replace($preset->urlPublic(), $imageUrl->urlPublic(), $description);
        }, $this->description);

        if ($presetsInvalid->count() > 0) {
            $this->update(compact('description'));
        }

        $presetsToSyncQuery = $this->getDescriptionMarkdownMediaPresetsValidQuery($mediaType);
        $mediaToSync = Media::whereIn('id', $presetsToSyncQuery->select('media_id'));

        return $this->syncMedia($mediaToSync->pluck('uid')->toArray(), $mediaType);
    }
}