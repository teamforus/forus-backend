<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Media\CloneMediaRequest;
use App\Http\Requests\Api\Media\StoreMediaRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\MediaResource;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class MediaController extends Controller
{
    /**
     * @var MediaService
     */
    private mixed $mediaService;

    /**
     * MediaController constructor.
     */
    public function __construct()
    {
        $this->mediaService = resolve('media');
    }

    /**
     * Display a listing of the resource.
     *
     * @param BaseFormRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(BaseFormRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Media::class);

        $media = Media::where([
            'identity_address' => $request->auth_address(),
        ]);

        if ($type = $request->get('type', false)) {
            $media->where(compact('type'));
        }

        return MediaResource::collection($media->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMediaRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     * @return MediaResource
     */
    public function store(StoreMediaRequest $request): MediaResource
    {
        $this->authorize('store', Media::class);
        $file = $request->file('file');

        try {
            $media = $this->mediaService->uploadSingle(
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

        return new MediaResource($media ?? null);
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

        return new MediaResource($media);
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
            $media = $this->mediaService->cloneMedia($media, $media->type, true, $syncPresets);

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

        return new MediaResource($media ?? null);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Media $media
     * @throws \Illuminate\Auth\Access\AuthorizationException|Exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Media $media): JsonResponse
    {
        $this->authorize('destroy', $media);

        $this->mediaService->unlink($media);

        return new JsonResponse([]);
    }
}
