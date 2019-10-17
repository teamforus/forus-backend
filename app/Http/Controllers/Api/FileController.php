<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\File\StoreFileRequest;
use App\Http\Resources\FileResource;
use App\Services\FileService\FileService;
use App\Services\FileService\Models\File;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FileController extends Controller
{
    /**
     * @var FileService
     */
    private $fileService;

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
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request) {
        return FileResource::collection(File::where([
            'identity_address' => auth()->user()->getAuthIdentifier()
        ])->paginate(
            $request->input('per_page', 20)
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFileRequest $request
     * @return FileResource
     */
    public function store(StoreFileRequest $request)
    {
        return new FileResource($this->fileService->uploadSingle(
            $request->file('file'),
            auth()->user()->getAuthIdentifier()
        ));
    }

    /**
     * Validate file store request
     * @param StoreFileRequest $request
     * @return string
     */
    public function storeValidate(StoreFileRequest $request)
    {
        return '';
    }

    /**
     * Display the specified resource.
     *
     * @param File $file
     * @return FileResource
     */
    public function show(File $file)
    {
        return new FileResource($file);
    }

    /**
     * Display the specified resource.
     *
     * @param File $file
     * @return FileResource
     */
    public function download(File $file)
    {
        return file_get_contents($file->urlPublic());
    }
}
