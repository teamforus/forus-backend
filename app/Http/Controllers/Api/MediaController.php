<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Media\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
        $this->mediaService = app()->make('media');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request
    ) {
        $this->authorize('index', Media::class);

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function store(StoreMediaRequest $request)
    {
        $this->authorize('store', Media::class);

        return new MediaResource($this->mediaService->uploadSingle(
            $request->file('file'),
            $request->input('type'),
            auth_address(),
            'jpg'
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Media $media
     * @return MediaResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Media $media)
    {
        $this->authorize('show', $media);

        return new MediaResource($media);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Media $media
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(Media $media)
    {
        $this->authorize('destroy', $media);

        $this->mediaService->unlink($media);

        return response("");
    }
}
