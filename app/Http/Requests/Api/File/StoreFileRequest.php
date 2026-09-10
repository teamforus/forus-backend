<?php

namespace App\Http\Requests\Api\File;

use App\Http\Requests\BaseFormRequest;
use App\Rules\FileTypeRule;
use App\Services\FileService\FilePdfPreviewService;
use App\Services\FileService\FileUploadConfigService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFileRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type' => $this->typeRule(),
            'file' => $this->fileRule(),
            'file_preview' => $this->filePreviewRule(),
        ];
    }

    /**
     * @param Validator $validator
     * @return void
     * @noinspection PhpUnused
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $uploadedFile = $this->file('file');
            $uploadedFile = $uploadedFile instanceof UploadedFile ? $uploadedFile : null;
            $type = $this->input('type');
            $type = is_string($type) ? $type : null;
            $filePdfPreviewService = resolve(FilePdfPreviewService::class);

            if (!$filePdfPreviewService->isPdfPreviewUpload($type, $uploadedFile)) {
                return;
            }

            if (!$filePdfPreviewService->hasPdfClientExtension($uploadedFile)) {
                $validator->errors()->add('file', trans('validation.file_pdf_preview.extension'));

                return;
            }

            if (!resolve(FileUploadConfigService::class)->isExtensionAllowed($type, 'pdf')) {
                $validator->errors()->add('file', trans('validation.file_pdf_preview.disabled'));
            }

            if ($this->hasFile('file_preview')) {
                $validator->errors()->add('file_preview', trans('validation.file_pdf_preview.file_preview'));
            }
        });
    }

    /**
     * @return array
     */
    public function fileRule(): array
    {
        $type = $this->get('type');

        if (!(new FileTypeRule())->passes('type', $type)) {
            return ['required', 'file', Rule::in([])];
        }

        $fileUploadConfigService = resolve(FileUploadConfigService::class);
        $mimes = $fileUploadConfigService->getAllowedExtensions($type);
        $maxSize = $fileUploadConfigService->getMaxSize($type);

        return [
            'required',
            'file',
            'mimes:' . implode(',', $mimes),
            'max:' . $maxSize,
        ];
    }

    /**
     * @return array
     */
    public function filePreviewRule(): array
    {
        return [
            'nullable',
            'file',
            'image',
            'dimensions:max_width=1000,max_height=1000',
        ];
    }

    /**
     * @return array
     */
    public function typeRule(): array
    {
        return ['required', new FileTypeRule()];
    }
}
