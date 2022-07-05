<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Media\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MediaController extends Controller
{
    /**
     * @var MediaService
     */
    private $mediaService;

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
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Media::class);

        $media = Media::query()->where([
            'identity_address' => auth_address()
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
     * @return MediaResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function store(StoreMediaRequest $request): MediaResource
    {
        $this->authorize('store', Media::class);
        $file = $request->file('file');

        try {
            if ($media = $this->mediaService->uploadSingle(
                (string) $file,
                $file->getClientOriginalName(),
                $request->input('type'),
                $request->input('sync_presets', [
                    'thumbnail'
                ])
            )) {
                $media->update([
                    'identity_address' => auth_address()
                ]);
            }
        } catch (\Throwable $e) {
            logger()->error(sprintf("Media uploading failed: %s", $e->getMessage()));
        }

        return new MediaResource($media ?? null);
    }

    /**
     * Display the specified resource.
     *
     * @param Media $media
     * @return MediaResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Media $media): MediaResource
    {
        $this->authorize('show', $media);

        return new MediaResource($media);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Media $media
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(Media $media): JsonResponse
    {
        $this->authorize('destroy', $media);

        $this->mediaService->unlink($media);

        return response()->json([]);
    }
}
