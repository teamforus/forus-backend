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
use Illuminate\Support\Facades\Validator;
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
     * @throws \Exception
     */
    public function store(StoreFileRequest $request): FileResource
    {
        $uploadedFile = $request->file('file');
        $file = $this->fileService->uploadSingle($uploadedFile, $request->input('type'));

        if ($request->input('type') === 'reimbursement_proof') {
            $isImage = Validator::make($request->only('file'), [
                'file' => 'required|file|image',
            ])->passes();

            $isPdf = !$isImage && Validator::make($request->only('file'), [
                'file' => 'required|file|mimes:pdf'
            ])->passes();

            if ($isImage) {
                $file
                    ->makePreview($request->file('file'), 'reimbursement_file_preview')
                    ->updateModelValue('identity_address', $request->auth_address());
            }

            if ($isPdf && $request->has('file_preview')) {
                $file
                    ->makePreview($request->file('file_preview'), 'reimbursement_file_preview')
                    ->updateModelValue('identity_address', $request->auth_address());
            }
        }

        return new FileResource($file->updateModelValue('identity_address', $request->auth_address()));
    }

    /**
     * Validate file store request
     *
     * @param StoreFileRequest $request
     * @return ?JsonResponse
     * @noinspection PhpUnused
     */
    public function storeValidate(StoreFileRequest $request): ?JsonResponse
    {
        return $request->authorize() ? new JsonResponse(): null;
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
