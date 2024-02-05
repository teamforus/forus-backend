<?php

namespace App\Rules\FundRequests\FundRequestRecords;

use App\Helpers\Validation;
use App\Rules\FundRequests\BaseFundRequestRule;
use App\Services\FileService\Models\File;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class FundRequestRecordFilesRule extends BaseFundRequestRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, mixed $value): bool
    {
        $criterion = $this->findCriterion($attribute);

        if (!$criterion) {
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        // when attachments are not requested the files must not be provided
        if (!$criterion->show_attachment) {
            return empty($value) || $this->reject(trans('validation.in', compact('attribute')));
        }

        // files must be a non empty array
        if (($validation = Validation::check($value, 'required|array|min:1'))->fails()) {
            return $this->reject($validation->errors()->first('value'));
        }

        // validate each file
        foreach ($value as $index => $file) {
            $validation = Validation::check($file, [
                'required',
                Rule::exists('files', 'uid')->where(function(Builder|File $builder) {
                    $builder->where('identity_address', $this->request->auth_address());
                    $builder->where('type', 'fund_request_record_proof');
                    $builder->whereNull('fileable_type');
                    $builder->whereNull('fileable_id');
                }),
            ], $attribute . '.' . $index);

            if ($validation->fails()) {
                return $this->reject($validation->errors()->first('value'));
            }
        }

        return true;
    }
}
