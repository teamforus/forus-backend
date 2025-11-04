<?php

namespace App\Traits;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Models\MediaPreset;
use App\Services\MediaService\Traits\HasMedia;
use App\Support\MarkdownParser;
use DOMDocument;
use DOMElement;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use League\CommonMark\Exception\CommonMarkException;
use Throwable;

/**
 * @property string $description
 * @property string $description_html
 * @mixin Eloquent
 * @mixin HasMedia
 */
trait HasMarkdownFields
{
    /**
     * @return array
     */
    public static function getMarkdownKeys(): array
    {
        return [
            'description',
        ];
    }

    /**
     * @throws CommonMarkException
     * @return string
     * @noinspection PhpUnused
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return $this->markdownToHtml('description');
    }

    /**
     * @throws CommonMarkException
     * @return string
     */
    public function descriptionToText(): string
    {
        return $this->markdownToText('description');
    }

    /**
     * @param string $key
     * @throws CommonMarkException
     * @return string
     */
    public function markdownToHtml(string $key): string
    {
        return resolve(MarkdownParser::class)->toHtml($this->$key ?: '');
    }

    /**
     * @param string $key
     * @throws CommonMarkException
     * @return string
     */
    public function markdownToText(string $key): string
    {
        return resolve(MarkdownParser::class)->toText($this->$key ?: '');
    }

    /**
     * @param string $key
     * @return Builder|Media
     */
    public function getMarkdownMediaQuery(string $key): Builder|Media
    {
        return Media::whereRelation('presets', function (Builder|MediaPreset $builder) use ($key) {
            $builder->whereIn('id', $this->getMarkdownMediaPresetsQuery($key)->select('id'));
        });
    }

    /**
     * @param string $mediaType
     * @param string $key
     * @throws CommonMarkException
     * @return Builder|Media
     */
    public function getMarkdownMediaPresetsValidQuery(string $mediaType, string $key): Builder|MediaPreset
    {
        $presets = $this->getMarkdownMediaPresetsQuery($key);

        return $presets->whereHas('media', function (Builder|Media $builder) use ($mediaType) {
            $builder->where('type', $mediaType);
            $builder->where(function (Builder $builder) {
                $builder->whereNull('mediable_id');
                $builder->orWhereHasMorph('mediable', $this->getMorphClass(), function (Builder $builder) {
                    $builder->where('mediable_id', $this->getKey());
                });
            });
        });
    }

    /**
     * @param string $mediaType
     * @param string $key
     * @throws CommonMarkException
     * @return Builder|Media
     */
    public function getMarkdownMediaPresetsInValidQuery(string $mediaType, string $key): Builder|MediaPreset
    {
        $presets = $this->getMarkdownMediaPresetsQuery($key);
        $validPresets = $this->getMarkdownMediaPresetsValidQuery($mediaType, $key);

        return (clone $presets)->whereNotIn('id', (clone $validPresets->select('id')));
    }

    /**
     * @param string $mediaType
     * @param string|array|null $key
     * @throws Throwable
     * @throws CommonMarkException
     * @return void
     */
    public function syncMarkdownMedia(string $mediaType, string|array $key = null): void
    {
        foreach ((array) ($key ?? self::getMarkdownKeys()) as $key) {
            $presetsInvalid = $this->getMarkdownMediaPresetsInValidQuery($mediaType, $key)->get();

            $value = $presetsInvalid->reduce(function ($value, MediaPreset $preset) use ($mediaType) {
                $newMedia = resolve('media')->cloneMedia($preset->media, $mediaType);
                $newMedia->update(['identity_address' => auth()->id()]);
                $newPreset = $newMedia->findPreset($preset->key);
                $imageUrl = $newPreset ?: $newMedia->presets()->where('key', '!=', 'original')->first();

                return str_replace($preset->urlPublic(), $imageUrl->urlPublic(), $value);
            }, $this->$key);

            if ($presetsInvalid->count() > 0) {
                $this->update([
                    $key => $value,
                ]);
            }

            $presetsToSyncQuery = $this->getMarkdownMediaPresetsValidQuery($mediaType, $key);
            $mediaToSync = Media::whereIn('id', $presetsToSyncQuery->pluck('media_id'))->get();

            foreach ($mediaToSync as $media) {
                $media->update(['meta->markdown_column' => $key]);
            }

            $this->syncMedia($mediaToSync->pluck('uid')->toArray(), $mediaType, [
                'meta->markdown_column' => $key,
            ]);
        }
    }

    /**
     * @throws CommonMarkException
     * @return bool
     */
    public function syncMarkdownTexts(): bool
    {
        return $this->update(array_reduce(static::getMarkdownKeys(), fn ($arr, $key) => [
            ...$arr,
            $key . '_text' => $this->markdownToText($key),
        ], []));
    }

    /**
     * @param string $key
     * @throws CommonMarkException
     * @return array
     */
    protected function getMarkdownMediaPaths(string $key): array
    {
        if (!$this->markdownToHtml($key)) {
            return [];
        }

        $htmlDom = new DOMDocument();

        $htmlDom->loadHTML($this->markdownToHtml($key), LIBXML_NOERROR);
        $images = $htmlDom->getElementsByTagName('img');
        $linksArray = [];

        /** @var DOMElement $image */
        foreach ($images as $image) {
            $imageSrc = $image->getAttribute('src');

            if (Str::contains($imageSrc, '/media/')) {
                $srcSegments = [];
                preg_match('/\/media\/.*/', $imageSrc, $srcSegments);

                if (!empty($srcSegments[0])) {
                    $linksArray[] = $srcSegments[0];
                }
            }
        }

        return $linksArray;
    }

    /**
     * @param string $key
     * @throws CommonMarkException
     * @return Builder|MediaPreset
     */
    protected function getMarkdownMediaPresetsQuery(string $key): Builder|MediaPreset
    {
        return MediaPreset::query()->whereIn('path', $this->getMarkdownMediaPaths($key));
    }
}
