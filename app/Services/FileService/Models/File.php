<?php

namespace App\Services\FileService\Models;

use App\Models\Traits\HasDbTokens;
use App\Models\Traits\UpdatesModel;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * App\Services\FileService\Models\File
 *
 * @property int $id
 * @property string|null $uid
 * @property string|null $original_name
 * @property string $type
 * @property string $ext
 * @property int $order
 * @property string $path
 * @property string $size
 * @property string $identity_address
 * @property int|null $fileable_id
 * @property string|null $fileable_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $fileable
 * @property-read string $url_public
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read Media|null $preview
 * @method static Builder|File newModelQuery()
 * @method static Builder|File newQuery()
 * @method static Builder|File query()
 * @method static Builder|File whereCreatedAt($value)
 * @method static Builder|File whereExt($value)
 * @method static Builder|File whereFileableId($value)
 * @method static Builder|File whereFileableType($value)
 * @method static Builder|File whereId($value)
 * @method static Builder|File whereIdentityAddress($value)
 * @method static Builder|File whereOrder($value)
 * @method static Builder|File whereOriginalName($value)
 * @method static Builder|File wherePath($value)
 * @method static Builder|File whereSize($value)
 * @method static Builder|File whereType($value)
 * @method static Builder|File whereUid($value)
 * @method static Builder|File whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class File extends Model
{
    use HasDbTokens, HasMedia, UpdatesModel;

    /**
     * @return MorphTo
     */
    public function fileable(): MorphTo {
        return $this->morphTo();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'original_name', 'fileable_id',
        'fileable_type', 'ext', 'uid', 'path', 'size', 'type', 'order'
    ];

    /**
     * @return MorphOne
     */
    public function preview(): MorphOne
    {
        return $this
            ->MorphOne(Media::class, 'mediable')
            ->where('type', 'reimbursement_file_preview');
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getUrlPublicAttribute(): string
    {
        return $this->urlPublic();
    }

    /**
     * @param $uid
     * @return File|Model
     */
    public static function findByUid($uid): ?File
    {
        return self::where(compact('uid'))->first();
    }

    /**
     * @return string
     */
    public function urlPublic(): string
    {
        return resolve('file')->urlPublic(ltrim($this->path, '/'));
    }

    /**
     * @return StreamedResponse
     */
    public function download(): StreamedResponse
    {
        return resolve('file')->download(ltrim($this->path, '/'));
    }

    /**
     * @return bool|null
     * @throws \Exception
     */
    public function unlink(): ?bool
    {
        return resolve('file')->unlink($this);
    }

    /**
     * @return string
     */
    public static function makeUid(): string
    {
        return self::makeUniqueToken('uid', '255');
    }

    /**
     * @return $this
     */
    public function updateModel(array $attributes = [], array $options = []): self
    {
        return tap($this)->update($attributes, $options);
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string $type
     * @return Media
     * @throws \Exception
     */
    public function makePreview(UploadedFile $uploadedFile, string $type): Media
    {
        $media = resolve('media')->uploadSingle(
            (string) $uploadedFile,
            $uploadedFile->getClientOriginalName(),
            $type
        );

        $this->attachMedia($media);

        return $media;
    }
}
