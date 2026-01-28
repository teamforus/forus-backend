<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Media\CloneMediaRequest;
use App\Http\Requests\Api\Media\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Services\MediaService\Models\Media;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class MediaController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMediaRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return MediaResource
     */
    public function store(StoreMediaRequest $request): MediaResource
    {
        $this->authorize('store', Media::class);
        $file = $request->file('file');

        try {
            $media = resolve('media')->uploadSingle(
                (string) $file,
                $file->getClientOriginalName(),
                $request->input('type'),
                $request->input('sync_presets', ['thumbnail'])
            );

            if ($media) {
                $media->update([
                    'identity_address' => $request->auth_address(),
                ]);
            }
        } catch (Throwable $e) {
            logger()->error(sprintf(
                "Media uploading failed: %s\n%s",
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
        }

        return MediaResource::create($media ?? null);
    }

    /**
     * Display the specified resource.
     *
     * @param Media $media
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return MediaResource
     */
    public function show(Media $media): MediaResource
    {
        $this->authorize('show', $media);

        return MediaResource::create($media);
    }

    /**
     * @param CloneMediaRequest $request
     * @param Media $media
     * @throws AuthorizationException
     * @return MediaResource
     */
    public function clone(CloneMediaRequest $request, Media $media): MediaResource
    {
        $this->authorize('clone', $media);

        try {
            $syncPresets = $request->input('sync_presets');
            $media = resolve('media')->cloneMedia($media, $media->type, true, $syncPresets);

            $media->update([
                'identity_address' => $request->auth_address(),
            ]);
        } catch (Throwable $e) {
            logger()->error(sprintf(
                "Media uploading failed: %s\n%s",
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
        }

        return MediaResource::create($media ?? null);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Media $media
     * @throws \Illuminate\Auth\Access\AuthorizationException|Throwable
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Media $media): JsonResponse
    {
        $this->authorize('destroy', $media);

        resolve('media')->unlink($media);

        return new JsonResponse([]);
    }
}
