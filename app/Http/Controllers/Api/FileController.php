<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\File\StoreFileRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\FileResource;
use App\Services\FileService\FileService;
use App\Services\FileService\Models\File;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * FileController constructor.
     */
    public function __construct()
    {
        $this->fileService = resolve('file');
    }

    /**
     * Display a listing of the resource.
     *
     * @param BaseFormRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(BaseFormRequest $request): AnonymousResourceCollection {
        return FileResource::queryCollection(File::where([
            'identity_address' => $request->auth_address(),
        ]));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFileRequest $request
     * @return FileResource
     */
    public function store(StoreFileRequest $request): FileResource
    {
        return new FileResource($this->fileService->uploadSingle(
            $request->file('file'),
            $request->input('type'),
            $request->auth_address(),
        ));
    }

    /**
     * Validate file store request
     *
     * @param StoreFileRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function storeValidate(StoreFileRequest $request): JsonResponse
    {
        return new JsonResponse();
    }

    /**
     * Display the specified resource.
     *
     * @param File $file
     * @return FileResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(File $file): FileResource
    {
        $this->authorize('show', $file);

        return new FileResource($file);
    }

    /**
     * Download file as stream
     *
     * @param File $file
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function download(File $file): StreamedResponse
    {
        $this->authorize('download', $file);

        return $file->download();
    }
}
