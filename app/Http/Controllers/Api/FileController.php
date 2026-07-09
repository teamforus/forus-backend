<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\File\StoreFileRequest;
use App\Http\Resources\FileResource;
use App\Services\FileService\FileArchiveService;
use App\Services\FileService\FilePdfPreviewService;
use App\Services\FileService\Models\File;
use App\Services\FileService\PdfPreviewUploadService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class FileController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return new JsonResponse([]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFileRequest $request
     * @throws Throwable
     * @return FileResource
     */
    public function store(StoreFileRequest $request): FileResource
    {
        $type = (string) $request->input('type');
        $uploadedFile = $request->file('file');
        $authIdentity = $request->identity();

        $fileService = resolve('file');
        $filePdfPreviewService = resolve(FilePdfPreviewService::class);
        $pdfPreviewUploadService = resolve(PdfPreviewUploadService::class);

        if ($filePdfPreviewService->isPdfPreviewUpload($type, $uploadedFile)) {
            return FileResource::create($pdfPreviewUploadService->store($uploadedFile, $type, $authIdentity?->address));
        }

        $file = $fileService->uploadSingle($uploadedFile, $type);

        if ($type === 'reimbursement_proof') {
            $isImage = Validator::make($request->only('file'), [
                'file' => 'required|file|image',
            ])->passes();

            $isPdf = !$isImage && Validator::make($request->only('file'), [
                'file' => 'required|file|mimes:pdf',
            ])->passes();

            if ($isImage) {
                $file->makePreview($request->file('file'), 'reimbursement_file_preview')->update([
                    'identity_address' => $authIdentity?->address,
                ]);
            }

            if ($isPdf && $request->has('file_preview')) {
                $file->makePreview($request->file('file_preview'), 'reimbursement_file_preview')->update([
                    'identity_address' => $authIdentity?->address,
                ]);
            }
        }

        $file->update([
            'identity_address' => $authIdentity?->address,
        ]);

        return FileResource::create($file);
    }

    /**
     * Validate file store request.
     *
     * @param StoreFileRequest $request
     * @return ?JsonResponse
     * @noinspection PhpUnused
     */
    public function storeValidate(StoreFileRequest $request): ?JsonResponse
    {
        return $request->authorize() ? new JsonResponse() : null;
    }

    /**
     * Display the specified resource.
     *
     * @param File $file
     * @throws AuthorizationException
     * @return FileResource
     */
    public function show(File $file): FileResource
    {
        $this->authorize('show', $file);

        return FileResource::create($file);
    }

    /**
     * Download file as stream.
     *
     * @param File $file
     * @throws AuthorizationException
     * @return StreamedResponse
     */
    public function download(File $file): StreamedResponse
    {
        $this->authorize('download', $file);

        return $file->download();
    }

    /**
     * @param File $file
     * @throws AuthorizationException
     * @throws Throwable
     * @return BinaryFileResponse
     */
    public function downloadArchive(File $file): BinaryFileResponse
    {
        $this->authorize('downloadArchive', $file);

        $fileName = "file-pdf-$file->uid.zip";
        $fileArchiveService = resolve(FileArchiveService::class);

        return response()
            ->download($fileArchiveService->makeArchive($file), $fileName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /**
     * @param File $file
     * @throws AuthorizationException
     * @throws Throwable
     * @return BinaryFileResponse
     */
    public function downloadPreviewArchive(File $file): BinaryFileResponse
    {
        $this->authorize('downloadPreviewArchive', $file);

        $fileName = "file-pdf-preview-$file->uid.zip";
        $fileArchiveService = resolve(FileArchiveService::class);

        return response()
            ->download($fileArchiveService->makePreviewArchive($file), $fileName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
